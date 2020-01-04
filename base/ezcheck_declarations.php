<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Déclaration des nouvelles tables de la base de données propres au plugin et ne correspondant pas à un objet.
 *
 * Le plugin déclare une nouvelle table de ce type qui est :
 * - `spip_types_controles`, qui contient les éléments descriptifs des types de contrôles disponibles,
 *
 * @pipeline declarer_tables_principales
 *
 * @param array $tables_principales Tableau global décrivant la structure des tables de la base de données
 *
 * @return array Tableau fourni en entrée et mis à jour avec les nouvelles déclarations
 */
function ezcheck_declarer_tables_principales($tables_principales) {

	// Table spip_types_noisettes
	$types_controles = array(
		'type_controle' => "varchar(255) DEFAULT '' NOT NULL",  // Identifiant du type de contrôle (nom du fichier)
		'fonction'      => "varchar(4) DEFAULT 'php' NOT NULL", // Indique la nature du contrôle : 'php' (génère des anomalies), html (pas d'anomalie, état des lieux via un squelette HTML)
		'nom'           => "text DEFAULT '' NOT NULL",          // Nom littéral du contrôle
		'descriptif'    => "text DEFAULT '' NOT NULL",          // Description du contrôle
		'periode'       => 'smallint DEFAULT 0 NOT NULL',       // Période en seconde d'activation du contrôle
		'priorite'      => 'smallint(6) DEFAULT 0 NOT NULL',    // Priorité de traitement du contrôle (génie)
		'actif'         => "varchar(3) DEFAULT 'oui' NOT NULL", // Indicateur d'activité du contrôle. Si 'non', aucun contrôle de ce type ne peut être réalisé
		'signature'     => "varchar(32) DEFAULT '' NOT NULL",   // MD5 du fichier de configuration du contrôle
		'maj'           => 'timestamp DEFAULT current_timestamp ON UPDATE current_timestamp',
	);

	$types_controles_cles = array(
		'PRIMARY KEY' => 'type_controle',
		'KEY actif'   => 'actif',
	);

	$tables_principales['spip_types_controles'] = array(
		'field' => &$types_controles,
		'key'   => &$types_controles_cles,
	);

	return $tables_principales;
}

/**
 * Déclaration des objets nécessaires au plugin.
 * Le plugin ajoute :
 * - l'objet contrôle qui correspond à une fonction lancée périodiquement ou à la demande. Un contrôle est une instance
 *   d'un type de contrôle.
 * - l'objet anomalie, qui résulte des contrôles.
 *
 * @pipeline declarer_tables_objets_sql
 *
 * @param array $tables_objet_sql Description des tables de la base.
 *
 * @return array Description des tables de la base complétée par celles du plugin.
 */
function ezcheck_declarer_tables_objets_sql($tables_objet_sql) {

	// Table spip_controles, description des contrôles manuels ou périodiques, instances d'un type de contrôle.
	$tables_objet_sql['spip_controles'] = array(
		'type'       => 'controle',
		'principale' => 'oui',
		'field'      => array(
			'id_controle'   => 'bigint(21) NOT NULL',
			'type_controle' => "varchar(255) DEFAULT '' NOT NULL",                // Type de contrôle réalisé
			'date'          => "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL", // Date de l'activation
			'activation'    => "varchar(4) DEFAULT 'auto' NOT NULL",              // Type d'activation 'auto' ou 'user'
			'id_auteur'     => 'bigint(21) NOT NULL',                             // Si activation 'user' id de l'admin
			'nb_anomalies'  => 'smallint DEFAULT 0 NOT NULL',                      // Nombre d'anomalies résultantes
		),
		'key' => array(
			'PRIMARY KEY'       => 'id_controle',
			'KEY type_controle' => 'type_controle',
		),
		'titre' => 'type_controle : id_controle',

		'champs_editables'  => array(),
		'champs_versionnes' => array(),
		'rechercher_champs' => array(),
		'tables_jointures'  => array(),

		// Textes standard
		'texte_retour'          => '',
		'texte_modifier'        => '',
		'texte_creer'           => '',
		'texte_creer_associer'  => '',
		'texte_signale_edition' => '',
		'texte_objet'           => 'controle:titre_controle',
		'texte_objets'          => 'controle:titre_controles',
		'info_aucun_objet'      => 'controle:info_aucun_controle',
		'info_1_objet'          => 'controle:info_1_controle',
		'info_nb_objets'        => 'controle:info_nb_controle',
		'texte_logo_objet'      => '',
	);

	// Table spip_anomalies, les résultats des contrôles
	$tables_objet_sql['spip_anomalies'] = array(
		'type'       => 'anomalie',
		'principale' => 'oui',
		// Déclaration des champs
		'field'      => array(
			'id_anomalie'   => 'bigint(21) NOT NULL',
			'id_controle'   => 'bigint(21) NOT NULL',                   // Id du contrôle ayant détecté l'anomalie
			'objet'         => "varchar(25) NOT NULL default ''",       // Type d'objet sur lequel porte l'anomalie
			'id_objet'      => 'bigint(21) NOT NULL default 0',         // Id de l'objet sur lequel porte l'anomalie
			'type_anomalie' => "varchar(127) DEFAULT '' NOT NULL",      // Identifiant d'un type d'anomalie
			'gravite'       => "varchar(1) DEFAULT 'e' NOT NULL",       // Gravité de l'anomalie : 'e' pour erreur, 'a' pour avertissement et 'i' pour info
			'statut'        => "varchar(10) DEFAULT 'publie' NOT NULL", // Statut de l'anomalie : 'publie', 'corrige', 'poubelle'
			'parametres'    => "text DEFAULT '' NOT NULL",              // Paramètres permettant d'expliquer l'anomalie
			'date'          => "datetime DEFAULT '0000-00-00 00:00:00' NOT NULL", // Date correspondant au statut courant
			'maj'           => 'timestamp DEFAULT current_timestamp ON UPDATE current_timestamp',
		),
		'key' => array(
			'PRIMARY KEY'         => 'id_anomalie',
			'KEY id_controle'     => 'id_controle',
			'KEY objet'           => 'objet',
			'KEY id_objet'        => 'id_objet',
			'KEY type_anomalie'   => 'type_anomalie',
		),
		'join'  => array(
			'id_anomalie' => 'id_anomalie',
			'id_controle' => 'id_controle',
		),
		'titre' => 'gravite-type_anomalie : id_anomalie',

		// Champs spéciaux et jointures
		'champs_editables'  => array(),
		'champs_versionnes' => array(),
		'rechercher_champs' => array(),
		'tables_jointures'  => array(),

		// Statuts
		'statut_textes_instituer' => array(
			'publie'   => 'anomalie:texte_statut_publie',
			'corrige'  => 'anomalie:texte_statut_corrige',
			'poubelle' => 'anomalie:texte_statut_poubelle',
		),
		'statut' => array(
			array(
				'champ'     => 'statut',
				'publie'    => 'publie',
				'previsu'   => 'publie',
				'exception' => array('statut', 'tout')
			)
		),
		'texte_changer_statut' => 'anomalie:texte_changer_statut_anomalie',

		// Textes standard
		'texte_retour'          => '',
		'texte_modifier'        => '',
		'texte_creer'           => '',
		'texte_creer_associer'  => '',
		'texte_signale_edition' => '',
		'texte_objet'           => 'anomalie:titre_anomalie',
		'texte_objets'          => 'anomalie:titre_anomalies',
		'info_aucun_objet'      => 'anomalie:info_aucun_anomalie',
		'info_1_objet'          => 'anomalie:info_1_anomalie',
		'info_nb_objets'        => 'anomalie:info_nb_anomalie',
		'texte_logo_objet'      => '',
	);

	return $tables_objet_sql;
}

/**
 * Déclaration des informations tierces (alias, traitements, jointures, etc)
 * sur les tables de la base de données modifiées ou ajoutées par le plugin.
 *
 * Le plugin se contente de déclarer les alias des tables et quelques traitements.
 *
 * @pipeline declarer_tables_interfaces
 *
 * @param array $interface Tableau global des informations tierces sur les tables de la base de données
 *
 * @return array Tableau fourni en entrée et mis à jour avec les nouvelles informations
 */
function ezcheck_declarer_tables_interfaces($interface) {

	// Les tables : permet d'appeler une boucle avec le *type* de la table uniquement
	$interface['table_des_tables']['types_controles'] = 'types_controles';
	$interface['table_des_tables']['controles'] = 'controles';
	$interface['table_des_tables']['anomalies'] = 'anomalies';

	// Les traitements
	// - table spip_controles : on desérialise les tableaux
	$interface['table_des_traitements']['PARAMETRES']['anomalies'] = 'unserialize(%s)';

	return $interface;
}
