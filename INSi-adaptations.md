# Adaptations INSi / e-CPS / ordonnance — justification documentaire

> Document d'accompagnement de la branche `feat/insi-pipeline`. Chaque adaptation
> est justifiée par des références factuelles aux documents officiels consultés :
> le guide d'implémentation de l'INS dans les logiciels (DNS, v3.0, 12/2024),
> le guide d'intégration du téléservice INSi (GIE SESAM-Vitale, SEL-MP-043,
> v05.00.01, 19/12/2025), le package documentaire v05.00.01 (WSDL/XSD/exemples
> PSC) et la spécification « Format datamatrix INS » (ANS, v2.2.20230926).

---

## 1. Contexte

La librairie mutualise les blocs sémantiques des applications de santé de Louise
(prescription médicale SaaS, à terme comptes rendus de cardiologie,
d'échocardiographie, SGL, coronarographie, prise de rendez-vous…). Trois
fonctionnalités cibles en préparation :

1. **Identité patient via le téléservice INSi** (récupération et vérification
   de l'INS) ;
2. **Connexion et identification du médecin via Pro Santé Connect (e-CPS)** ;
3. **Ordonnance numérique** (PDF signé, cartouche INS + datamatrix).

Les adaptations suivantes préparent ces trois points **sans** voir le code des
applications consommatrices (agrégats, persistance et workflows restent
applicatifs, conformément à la philosophie de la librairie : « consumer owns
aggregates »).

---

## 2. Adaptations livrées

### 2.1 `Identity\ValueObject\IdentityAttribute` + `IdentityAttributes`

**Pourquoi.** Le référentiel français distingue, au-delà des 4 statuts
fonctionnels, **3 attributs** caractérisant les identités nécessitant un
traitement particulier, avec des invariants contraignants :

- **homonyme** : compatible avec tous les statuts ; vigilance particulière
  (identités à fort taux de ressemblance) ;
- **douteux** : doute sur la véracité de l'identité ; uniquement compatible
  avec le statut « provisoire » ; **appel au téléservice INSi bloqué** ;
  matricule INS et OID **invalidés** ;
- **fictif** : identités sensibles ou imaginaires (tests informatiques,
  formation) ; mêmes contraintes que « douteux » ;
- « fictif » et « douteux » ne peuvent pas être cumulés ; « homonyme » peut
  être cumulé avec l'un ou l'autre.

**Références factuelles.**
- Guide d'implémentation INS (DNS v3.0), ex. **EXI ID 24** (attributs) et
  **EXI ID 26** (règles : statut ≠ provisoire impossible, appel INSi bloqué,
  invalidation matricule/OID) ;
- RNIV (volet commun), ex. EXI SI 22/SI 09.

**Décisions de conception.** Enum + collection immuable qui valide la règle de
cumul à la construction et expose `requiresProvisionalStatus()` /
`blocksInsiLookup()` (les deux découlent du même invariant) et
`combinesWith()` (règle de cumul). La machine à états applicable (transitions,
rétrogradation), elle, reste côté application car elle dépend du statut et des
workflows applicatifs.

### 2.2 `Identity\Service\InsiTraitsNormalizer`

**Pourquoi.** Le téléservice INSi **rejette** toute donnée alphanumérique en
entrée (et en sortie) contenant des minuscules, des signes diacritiques
(accents, trémas, cédilles) ou des ligatures (Æ, Œ). Le jeu de caractères
autorisé est : A-Z majuscules, espace, apostrophe `'` (ASCII 39), tiret `-` et
double tiret `--` (ASCII 45). Des règles syntaxiques s'appliquent : le premier
caractère ne peut être ni un blanc ni un tiret (l'apostrophe est admise en
première position) ; un blanc et une apostrophe ne peuvent pas être doublés ni
combinés.

**Références factuelles.**
- SEL-MP-043 v05.00.01, §2 « Limitations du téléservice » (aucune minuscule,
  signe diacritique ou ligature) ;
- SEL-MP-043 v05.00.01, §3.4.1 (EF_INS01_03, EF_INS10_01 et EF_INS10_02 :
  formats, règles syntaxiques du premier caractère, doublage/espace+apostrophe) ;
- ANS « Format datamatrix INS » v2.2, §5 (S3 et S4 : majuscules sans accent ni
  signe diacritique, tirets et apostrophes autorisés).

**Décisions de conception.** Service stateless, **sans dépendance** à `intl` ni
`mbstring` (carte `strtr` déterministe) pour rester compatible avec la CI
(qui n'installe que `mbstring`). Distinction nette : la librairie **préserve**
les valeurs stockées (« trimmed only », politique de `StrictIdentityTraits`) ;
la normalisation stricte n'est appliquée qu'**au moment de l'appel INSi ou de
l'encodage du datamatrix**. `normalize()` (profil lexical) et `isValid()`
(règles syntaxiques) sont exposés séparément.

### 2.3 `Care\ValueObject\InsurancePractice`

**Pourquoi.** Chaque opération INSi (WS_INS1 à WS_INS5) transporte une
**assertion PS** dont l'`AttributeStatement` doit contenir au minimum :
`identifiantFacturation` (pour les établissements : FINESS géographique sinon
SIRET ; pour les autres acteurs, dont les libéraux : identifiant AM de
facturation — le guide recommande de le rendre **paramétrable** dans le
logiciel), `codeSpecialite` (**obligatoire pour les médecins** et médecins en
formation) et `secteurActivite`.

**Références factuelles.**
- SEL-MP-043 v05.00.01, §3.2 « Renseignement des Assertions et Contextes »
  (tableau des champs à renseigner par opération) ;
- Exemple officiel de requête PSC (`WS_INS2_rechercherInsAvecTraitsIdentite_
  requête_avec_header.xml`, package v05.00.01) : assertion SAML non signée,
  `NameID NameQualifier="PSC"`, attributs `codeSpecialiteAMO` et
  `identifiantFacturation` ;
- Cadre d'Interopérabilité des TLSi AMO [CI] (valeurs des codes).

**Décisions de conception.** Valeurs **non figées dans des enums** : les tables
de codes sont gouvernées par des référentiels externes évolutifs ([CI],
TRE_R38 pour la spécialité ordinale — distincte du `codeSpecialite` AMO).
Champs trimés et non vides. La spécialité ordinale (`SavoirFaireCode`/TRE_R38)
existe déjà dans la librairie et **ne doit pas être confondue** avec le
`codeSpecialite` AMO de l'assertion.

### 2.4 `Identity\Service\InsiDatamatrixPayload`

**Pourquoi.** Depuis 2021 tout document de santé référencé avec une INS
**qualifiée** doit porter, en clair **et sous forme de code à barres**, les
données obligatoires de l'identité (ex. EXI DIF 02 du guide d'implémentation ;
spécification ANS du datamatrix INS). La chaîne à encoder a une structure
normative précise.

**Références factuelles.**
- ANS « Format datamatrix INS » v2.2.20230926 :
  - §3.1-3.2 : en-tête fixe de **26 caractères** ([A-Z][0-9], encodé C40) :
    marqueur `IS` (pos. 0-1), version `01` (pos. 2-3), 22 caractères réservés
    à `0` (pos. 4-25) ;
  - §3.3 : zone de message = blocs [ID sur 2 caractères] [valeur] ; règle du
    séparateur `<GS>` (ASCII 29) : champ de longueur variable ni à sa longueur
    max ni en dernière position → terminé par `<GS>` ; champ de longueur fixe
    → pas de séparateur ; dernier champ → pas de séparateur ;
  - §5 : S1 matricule INS (fixe 15), S2 OID (19-20), S3 liste des prénoms,
    S4 nom de naissance (majuscules sans accent, tirets/apostrophes), S5 sexe
    (fixe 1, M/F), S6 date de naissance (fixe 10, **format JJ-MM-AAAA**),
    S7 code lieu de naissance COG (fixe 5, facultatif) ;
  - §4 : représentation graphique ISO/IEC 16022, ECC200, quiet zone ≥ 1
    module, mention « INS à scanner » (hors périmètre de cette classe, couche
    de rendu) ;
  - §1 : outil de validation ANS : https://interop.esante.gouv.fr/
    (validation obligatoire de chaque payload généré, à intégrer en CI).
- SEL-MP-043 §2 (profil lexical des noms, appliqué via
  `InsiTraitsNormalizer`) ; guide d'implémentation INS v3.0, ex. EXI DIF 02
  (première page d'un document de santé) et EXI ID 29 (transmission du
  matricule uniquement si identité qualifiée).

**Décisions de conception.** La classe construit **la chaîne** (en-tête +
message) ; le rendu graphique (bibliothèque datamatrix ECC200, C40 de l'en-tête,
marquage) reste dans la couche de rendu applicative. Le sexe indéterminé (I)
est rejeté : INSi ne délivre que F/M (EF_INS25_04). La date passe du format de
stockage AAAA-MM-JJ au format datamatrix JJ-MM-AAAA.

---

## 3. Reste à faire (recommandations suivantes, non livrées ici)

| Sujet | Référence | Note |
|---|---|---|
| `BirthDate` (date fictive/incertaine) | Guide INS v3.0, EXI ID 11-12 (marqueur « date fictive/incertaine » ; règles de complétion 01/MM, JJ/01, 31/12) | Distingue identité sanitaire vs facturation |
| `UsualGivenName` (prénom utilisé) | Guide INS v3.0, EXI ID 08 (jamais auto-alimenté) ; RNIV EXI PP 18 | `HumanName` couvre le nom utilisé, pas le prénom utilisé |
| Cohérence 1er prénom ↔ début de liste | Guide INS v3.0, EXI ID 10 (tirets/apostrophes ≡ espaces, alerte) | Utile au-delà : recherche d'antériorité |
| `InsIdentifierHistory` (matricules historisés) | Guide INS v3.0, EXI REC 08 (conservation à l'identique, historique) ; SEL-MP-043 §3.4.2 (INSHISTO) | Limite : 10 changements (régime général, après 2006) |
| `IdentificationEvidence` (justificatif + confiance) | Guide INS v3.0, EXI ID 19 (dispositif à haut niveau de confiance, paramétrable) | Exigence organisationnelle RNIV |
| `DoseInstruction` / `PrescriptionLine` | Ordonnance structurée (V1/V2) | Ne pas verrouiller le format (ordonnance simple vs SCOR) |

Les pistes PSC (bac à sable) et CNDA (n° d'autorisation de test) sont en cours
côté démarches ; le client SOAP (`InsiSoapClient`) et la passerelle
(`INsiGateway` + Mock) se construisent ensuite sur ces blocs.

---

## 4. Contexte de revue (pour la relecture secondaire)

- Branche : `feat/insi-pipeline` (un commit par adaptation, messages détaillés).
- Consommateur prévu : application SaaS de prescription (Symfony 8.1 /
  API Platform 4 / PostgreSQL ; PHP 8.5), puis applications de compte rendu
  (cardiologie, échocardiographie, SGL…).
- Contraintes : PHP >= 8.2, zéro dépendance runtime, CI PHP 8.2→8.5
  (extensions : `mbstring` uniquement), PHPStan niveau 8, PSR-12.
- Politique : la librairie ne possède ni persistance, ni agrégats applicatifs,
  ni UI — uniquement de la sémantique de domaine et des services de domaine
  purs.