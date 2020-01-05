<?php
/**
 * Ce fichier contient les fonctions de service nécessité par le plugin Check Factory.
 */
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Renvoie la configuration spécifique des caches de Check Factory.
 *
 * @param string $plugin Identifiant qui permet de distinguer le module appelant qui peut-être un plugin comme le noiZetier ou
 *                       un script. Pour un plugin, le plus pertinent est d'utiliser le préfixe.
 *
 * @return array Tableau de la configuration brute du plugin Check Factory.
 */
function ezcheck_cache_configurer($plugin) {

	// Initialisation du tableau de configuration avec les valeurs par défaut du plugin Check Factory.
	$configuration = array(
		'racine'          => '_DIR_CACHE',
		'sous_dossier'    => false,
		'nom_obligatoire' => array('nom'),
		'nom_facultatif'  => array(),
		'extension'       => '.php',
		'securisation'    => true,
		'serialisation'   => true,
		'decodage'        => false,
		'separateur'      => '-',
		'conservation'    => 0
	);

	return $configuration;
}
