<?php
/**
 * Ce fichier contient les fonctions de création, de mise à jour et de suppression
 * du schéma de données propres au plugin (tables et configuration).
 */
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}
/**
 * Installation du schéma de données propre au plugin et gestion des migrations suivant
 * les évolutions du schéma.
 *
 * Le schéma comprend des tables et des variables de configuration.
 *
 * @api
 *
 * @param string $nom_meta_base_version Nom de la meta dans laquelle sera rangée la version du schéma
 * @param string $version_cible         Version du schéma de données en fin d'upgrade
 *
 * @return void
 */
function ezcheck_upgrade($nom_meta_base_version, $version_cible) {
	$maj = array();

	// Création des tables
	$maj['create'] = array(
		array('maj_tables', array('spip_types_controles', 'spip_controles', 'spip_anomalies')),
	);

	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}

/**
 * Suppression de l'ensemble du schéma de données propre au plugin, c'est-à-dire
 * les tables et les variables de configuration.
 *
 * @api
 *
 * @param string $nom_meta_base_version Nom de la meta dans laquelle sera rangée la version du schéma
 *
 * @return void
 */
function ezcheck_vider_tables($nom_meta_base_version) {

	// On efface les jobs associés aux contrôles (spip_jobs et spip_jobs_liens)

	// On efface les tables des contrôles et anomalies
	sql_drop_table('spip_types_controles');
	sql_drop_table('spip_controles');
	sql_drop_table('spip_anomalies');

	// on efface la meta du schéma du plugin
	effacer_meta($nom_meta_base_version);
}
