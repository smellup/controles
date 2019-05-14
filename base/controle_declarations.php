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
 * Déclaration des objets du plugin.
 * Le plugin ajoute :
 * - l'objet contrôle (vérification automatique ou à la demande),
 * - l'objet anomalie, produit des contrôles.
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

	$tables['spip_controles'] = array(
		'type' => 'controle',
		'principale' => 'oui',
		'field'=> array(
			'id_controle'   => "bigint(21) NOT NULL",
			'nom'           => "varchar(32) DEFAULT '' NOT NULL",
			'descriptif'    => "text DEFAULT '' NOT NULL",
			'date'          => "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
			'maj'           => "TIMESTAMP",
		),
		'key' => array(
			'PRIMARY KEY'   => 'id_controle',
			'KEY nom'       => 'nom',
		),
        'titre' => "nom",

        'champs_editables'  => array(),
        'champs_versionnes' => array(),
        'rechercher_champs' => array(),
        'tables_jointures'  => array(),

		// Textes standard
		'texte_retour' 			=> '',
		'texte_modifier' 		=> '',
		'texte_creer' 			=> '',
		'texte_creer_associer' 	=> '',
		'texte_signale_edition' => '',
		'texte_objet' 			=> '',
		'texte_objets' 			=> '',
		'info_aucun_objet'		=> '',
		'info_1_objet' 			=> '',
		'info_nb_objets' 		=> '',
		'texte_logo_objet' 		=> '',
	);

	$tables['spip_anomalies'] = array(
		'type' => 'anomalie',
		'principale' => 'oui',
		'field'=> array(
			'id_anomalie'   => "bigint(21) NOT NULL",
			'id_controle'   => "bigint(21) NOT NULL default 0",
			'objet'         => "varchar(25) NOT NULL default ''",
			'id_objet'      => "bigint(21) NOT NULL default 0",
			'gravite'       => "varchar(1) DEFAULT 'e' NOT NULL",
			'type_anomalie' => "varchar(127) DEFAULT '' NOT NULL",
			'statut'        => "varchar(10) DEFAULT 'publie' NOT NULL",
			'parametres'    => "text DEFAULT '' NOT NULL",
			'date'          => "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL",
			'maj'           => "TIMESTAMP",
		),
		'key' => array(
			'PRIMARY KEY'       => 'id_anomalie',
			'KEY id_controle'   => 'id_controle',
			'KEY objet'         => 'objet',
			'KEY id_objet'      => 'id_objet',
			'KEY type_anomalie' => 'type_anomalie',
		),
        'titre' => 'gravite-type_erreur : id_erreur',

        'champs_editables'  => array(),
        'champs_versionnes' => array(),
        'rechercher_champs' => array(),
        'tables_jointures'  => array(),
        'statut_textes_instituer' => array(
            'publie'   => 'anomalie:texte_statut_publie',
            'corrige'  => 'anomalie:texte_statut_corrige',
            'poubelle' => 'anomalie:texte_statut_poubelle',
        ),
        'statut'=> array(
            array(
                'champ'     => 'statut',
                'publie'    => 'publie',
                'previsu'   => 'publie',
                'exception' => array('statut', 'tout')
            )
        ),
        'texte_changer_statut' => 'anomalie:texte_changer_statut_anomalie',

		// Textes standard
		'texte_retour' 			=> '',
		'texte_modifier' 		=> '',
		'texte_creer' 			=> '',
		'texte_creer_associer' 	=> '',
		'texte_signale_edition' => '',
		'texte_objet' 			=> 'anomalie:titre_taxon',
		'texte_objets' 			=> 'anomalie:titre_taxons',
		'info_aucun_objet'		=> 'anomalie:info_aucun_taxon',
		'info_1_objet' 			=> 'anomalie:info_1_taxon',
		'info_nb_objets' 		=> 'anomalie:info_nb_taxons',
		'texte_logo_objet' 		=> '',
	);

	return $tables;
}
