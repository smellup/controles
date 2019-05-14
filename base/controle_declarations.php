<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Déclaration des informations tierces (alias, traitements, jointures, etc)
 * sur les tables de la base de données modifiées ou ajoutées par le plugin.
 *
 * Le plugin se contente de déclarer les alias des tables et quelques traitements.
 *
 * @pipeline declarer_tables_interfaces
 *
 * @param array $interface
 * 		Tableau global des informations tierces sur les tables de la base de données
 * @return array
 *		Tableau fourni en entrée et mis à jour avec les nouvelles informations
 */
function contrib_declarer_tables_interfaces($interface) {

	// Les tables : permet d'appeler une boucle avec le *type* de la table uniquement
	$interface['table_des_tables']['controles'] = 'controles';
	$interface['table_des_tables']['anomalies'] = 'anomalies';

	// Les traitements
	// - table spip_controles : on desérialise les tableaux
	$interface['table_des_traitements']['PARAMETRES']['anomalies'] = 'unserialize(%s)';

	return $interface;
}


/**
 * Déclaration des nouvelles tables de la base de données propres au plugin.
 *
 * Le plugin déclare trois nouvelles tables qui sont :
 *
 * - `spip_controles`, qui contient les contrôles possibles et leur activité.
 * - `spip_erreurs`, qui contient les erreurs détectées suite aux contrôles effectués.
 *
 * @pipeline declarer_tables_principales
 *
 * @param array $tables_principales
 *		Tableau global décrivant la structure des tables de la base de données
 * @return array
 *		Tableau fourni en entrée et mis à jour avec les nouvelles déclarations
 */
function contrib_declarer_tables_principales($tables_principales) {

	// Table spip_erreurs
	$erreurs = array(
		'id_erreur'     => 'bigint(21) NOT NULL',
		'objet'         => 'varchar(25) NOT NULL default ""',
		'id_objet'      => 'bigint(21) NOT NULL default 0',
		'type_erreur'   => "varchar(127) DEFAULT '' NOT NULL",
		'statut'        => "varchar(10) DEFAULT '0' NOT NULL",
		'parametres'    => "text DEFAULT '' NOT NULL",
		'date'          => "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
		'maj'           => 'TIMESTAMP',
	);

	$erreurs_cles = array(
		'PRIMARY KEY'       => 'id_erreur',
		'KEY objet'         => 'objet',
		'KEY id_objet'      => 'id_objet',
		'KEY type_erreur'   => 'type_erreur',
	);

	$tables_principales['spip_erreurs'] = array(
		'field' => &$erreurs,
		'key'   => &$erreurs_cles,
	);

	return $tables_principales;
}
/**
 * Déclaration des objets du plugin. Le plugin ajoute l'objet erreur de contrôle au travers de la
 * seule table `spip_erreurs`. Les contrôles ne sont pas matérialisés.
 *
 * L'objet taxon est défini comme une arborescence de taxons du règne au rang le plus petit dans le règne.
 * Les taxons de rang égal ou inférieur à l'espèce font aussi partie de cette table. Les champs principaux sont les
 * suivants :
 *        - `nom_scientifique` est le nom en latin. Il est unique pour un rang taxonomique donné.
 *        - `rang` taxonomique est une valeur parmi `kingdom`, `phylum`, `class`, `order`, `family`, `genus`, `species`...
 *        - `nom_commun` est le nom vulgaire, si possible normalisé par une commission officielle. Il peut coïncider ou
 *           pas avec le nom vernaculaire.
 *        - `auteur` est une information composée d'un ou plusieurs noms complétés par une date (ex : Linneus, 1798).
 *        - `tsn` est l'identifiant numérique unique du taxon dans la base taxonomique ITIS.
 *        - `tsn_parent` permet de créer l'arborescence taxonomique du règne conformément à l'organisation de la base
 *        ITIS.
 *        - `espece` indique si oui ou non le taxon à un rang supérieur ou inférieur ou égal à `species`.
 *
 * @pipeline declarer_tables_objets_sql
 *
 * @param array $tables
 *        Description des tables de la base.
 *
 * @return array
 *        Description des tables de la base complétée par celles du plugin.
 */
function controle_declarer_tables_objets_sql($tables) {

	$tables['spip_taxons'] = array(
		'type' => 'taxon',
		'principale' => 'oui',
		'field'=> array(
			'id_taxon'          => "bigint(21) NOT NULL",
			'nom_scientifique'	=> "varchar(35) DEFAULT '' NOT NULL",
			'indicateurs'       => "varchar(32) DEFAULT '' NOT NULL",
			'rang_taxon'		=> "varchar(15) DEFAULT '' NOT NULL",
			'regne'				=> "varchar(10) DEFAULT '' NOT NULL",
			'nom_commun'		=> "text DEFAULT '' NOT NULL",
			'auteur'			=> "varchar(100) DEFAULT '' NOT NULL",
			'descriptif'		=> "text DEFAULT '' NOT NULL",
			'texte'             => "longtext DEFAULT '' NOT NULL",
			'tsn'				=> "bigint(21) NOT NULL",
			'tsn_parent'		=> "bigint(21) NOT NULL",
			'sources'           => "text NOT NULL",
			'importe'           => "varchar(3) DEFAULT 'non' NOT NULL",
			'edite'             => "varchar(3) DEFAULT 'non' NOT NULL",
			'espece'            => "varchar(3) DEFAULT 'non' NOT NULL",
			'statut'            => "varchar(10) DEFAULT 'prop' NOT NULL",
			'maj'				=> "TIMESTAMP"
    ),
		'key' => array(
			'PRIMARY KEY' => 'id_taxon',
            'KEY tsn'     => 'tsn',
			'KEY statut'  => 'statut',
			'KEY espece'  => 'espece',
			'KEY importe' => 'importe',
			'KEY edite'   => 'edite',
		),
        'titre' => "nom_scientifique AS titre, '' AS lang",

        'champs_editables'  => array('nom_commun', 'descriptif', 'texte', 'sources'),
        'champs_versionnes' => array('nom_commun', 'descriptif', 'texte', 'sources'),
        'rechercher_champs' => array('nom_scientifique' => 10, 'nom_commun' => 10, 'auteur' => 2, 'descriptif' => 5, 'texte' => 5),
        'tables_jointures'  => array(),
        'statut_textes_instituer' => array(
            'prop'     => 'taxon:texte_statut_prop',
            'publie'   => 'taxon:texte_statut_publie',
            'poubelle' => 'taxon:texte_statut_poubelle',
        ),
        'statut'=> array(
            array(
                'champ'     => 'statut',
                'publie'    => 'publie',
                'previsu'   => 'publie,prop',
                'exception' => array('statut', 'tout')
            )
        ),
        'texte_changer_statut' => 'taxon:texte_changer_statut_taxon',

		// Textes standard
		'texte_retour' 			=> 'icone_retour',
		'texte_modifier' 		=> 'taxon:icone_modifier_taxon',
		'texte_creer' 			=> 'taxon:icone_creer_taxon',
		'texte_creer_associer' 	=> '',
		'texte_signale_edition' => '',
		'texte_objet' 			=> 'taxon:titre_taxon',
		'texte_objets' 			=> 'taxon:titre_taxons',
		'info_aucun_objet'		=> 'taxon:info_aucun_taxon',
		'info_1_objet' 			=> 'taxon:info_1_taxon',
		'info_nb_objets' 		=> 'taxon:info_nb_taxons',
		'texte_logo_objet' 		=> 'taxon:titre_logo_taxon',
	);

	return $tables;
}
