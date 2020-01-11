<?php
/**
 * Ce fichier contient l'API de gestion des types de contrôle.
 */
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Charge ou recharge la configuration des dashboards à partir de leur fichier YAML.
 * La fonction compile les dashboards dans un cache unique sécurisé.
 *
 * @api
 *
 * @param bool $recharger
 *
 * @return bool `false` si une erreur s'est produite, `true` sinon.
 */
function dashboard_charger($recharger = false) {

	// Retour de la fonction
	$retour = true;

	// On recherche les dashboards directement par leur fichier YAML de configuration car il est
	// obligatoire. La recherche s'effectue dans le path en utilisant le dossier relatif fourni.
	if ($fichiers = find_all_in_path('ezcheck/dashboards/', '.+[.](yaml|json)$')) {
		// Initialisation du tableau des dashboard
		$dashboards = array();

		// Récupération de la description complète des dashboards déjà enregistrés de façon :
		// - à comparer les signatures md5 des noisettes déjà enregistrées. Si on force le rechargement il est inutile
		//   de gérer les signatures et les contrôles modifiés ou obsolètes.
		$dashboards_existants = !$recharger ? dashboard_lister() : array();

		foreach ($fichiers as $_config => $_chemin) {
			// Détermination de l'exension du fichier json ou yaml.
			$extension = pathinfo($_config, PATHINFO_EXTENSION);

			// L'identifiant du dashboard est son nom de fichier sans extension
			$dashboard_id = basename($_config, ".${extension}");

			// Initialisation de la description par défaut du type de contrôle
			$description_defaut = array(
				'identifiant'   => $dashboard_id,
				'nom'           => $dashboard_id,
				'description'   => '',
				'icone'         => 'dashboard-24.png',
				'boite'         => '',
				'groupes'       => array(),
				'signature'     => '',
			);

			// Si on a forcé le rechargement ou si aucun md5 n'est encore stocké pour le dashboard
			// on positionne la valeur du md5 stocké à chaine vide.
			// De cette façon, on force la lecture du fichier JSON/YAML du dashboard.
			$md5_stocke = (isset($dashboards_existants[$dashboard_id]['signature']) and !$recharger)
				? $dashboards_existants[$dashboard_id]['signature']
				: '';

			// On vérifie que le md5 du fichier JSON/YAML est bien différent de celui stocké avant de charger
			// le contenu. Sinon, on passe au fichier suivant.
			$md5 = md5_file($_chemin);
			if ($md5 != $md5_stocke) {
				// Lecture et décodage du fichier YAML ou JSON en structure de données PHP.
				if ($extension == 'json') {
					include_spip('inc/flock');
					lire_fichier($_chemin, $dashboard_contenu);
					$description = json_decode($dashboard_contenu, true);
				} else {
					include_spip('inc/yaml');
					$description = yaml_decode_file($_chemin, array('include' => false));
				}

				$description['signature'] = $md5;
				// Complétude de la description avec les valeurs par défaut
				$description = array_merge($description_defaut, $description);

				// On reformate le tableau des groupes pour que l'index soit l'identifiant
				$groupes = array();
				foreach ($description['groupes'] as $_groupe) {
					$groupes[$_groupe['identifiant']] = $_groupe;
				}
				$description['groupes'] = $groupes;

				// On ajoute le dashboard nouveau ou modifié
				$dashboards[$dashboard_id] = $description;
			} else {
				// Le dashboard n'est pas modifié
				// => Il faut l'ajouter tel quel dans le tableau
				$dashboards[$dashboard_id] = $dashboards_existants[$dashboard_id];
			}
		}

		// Etant donné que le nombre dashboard est réduit tout comme les informations qui le compose on choisit
		// de l'écrire systématiquement.
		// -- Initialisation de l'identifiant du cache des dashboards
		$cache = array(
			'nom' => 'dashboards',
		);
		// -- Encodage du contenu en JSON
		$contenu = json_encode($dashboards, true);
		// -- Ecriture du cache sécurisé
		cache_ecrire('ezcheck', $cache, $contenu);
	}

	return $retour;
}

/**
 * Renvoie l'information brute demandée pour l'ensemble des contrôles utilisés
 * ou toute les descriptions si aucune information n'est explicitement demandée.
 *
 * @param string $information Identifiant d'un champ de la description d'un contrôle.
 *                            Si l'argument est vide, la fonction renvoie les descriptions complètes et si l'argument est
 *                            un champ invalide la fonction renvoie un tableau vide.
 *
 * @return array Tableau de la forme `[type_controle] = information ou description complète`. Les champs textuels
 *               sont retournés en l'état, le timestamp `maj n'est pas fourni.
 */
function dashboard_lister($information = '') {

	// Initialiser le tableau de sortie en cas d'erreur
	$dashboards = $information ? '' : array();

	// Les dashboards sont stockées dans un cache sécurisé géré par Cache Factory.
	// -- Initialisation de l'identifiant du cache des dashboards
	$cache = array(
		'nom' => 'dashboards',
	);

	include_spip('inc/cache');
	if ($descriptions = cache_lire('ezcheck', $cache)) {
		if ($information) {
			// Si $information n'est pas une colonne valide array_column retournera un tableau vide.
			if ($informations = array_column($descriptions, $information, 'identifiant')) {
				$dashboards = $informations;
			}
		} else {
			$dashboards = $descriptions;
		}
	}

	return $dashboards;
}
