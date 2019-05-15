<?php
/**
 * Ce fichier contient l'API de gestion des contrôles.
 */
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}


/**
 * Charge ou recharge les descriptions des contrôles à partir des fichiers YAML.
 * La fonction optimise le chargement en effectuant uniquement les traitements nécessaires
 * en fonction des modifications, ajouts et suppressions des contrôles identifiés
 * en comparant les md5 des fichiers YAML.
 *
 * @api
 *
 * @param bool   $recharger
 *        Si `true` force le rechargement de tous les types de noisette, sinon le chargement se base sur le
 *        md5 des fichiers YAML. Par défaut vaut `false`.
 *
 * @return bool
 *        `false` si une erreur s'est produite, `true` sinon.
 */
function controle_charger($recharger) {

	// Retour de la fonction
	$retour = true;

	// On recherche les contrôles directement par leur fichier YAML de configuration car il est
	// obligatoire. La recherche s'effectue dans le path en utilisant le dossier relatif fourni.
	if ($fichiers = find_all_in_path('controles/', '.+[.]yaml$')) {
		// Initialisation des tableaux de types de noisette.
		$controles_a_ajouter = $controles_a_changer = $controles_a_effacer = array();

		// Récupération de la description complète des types de noisette déjà enregistrés de façon :
		// - à gérer l'activité des types en fin de chargement
		// - de comparer les signatures md5 des noisettes déjà enregistrées. Si on force le rechargement il est inutile
		//   de gérer les signatures et les noisettes modifiées ou obsolètes.
		$controles_existants = ncore_type_noisette_lister($plugin, '', $stockage);
		$signatures = array();
		if (!$recharger) {
			$signatures = array_column($controless_existantes, 'signature', 'type_noisette');
			// On initialise la liste des types de noisette à supprimer avec l'ensemble des types de noisette déjà stockés.
			$controles_a_effacer = $signatures ? array_keys($signatures) : array();
		}

		foreach ($fichiers as $_squelette => $_chemin) {
			$type_noisette = basename($_squelette, '.yaml');
			// Si on a forcé le rechargement ou si aucun md5 n'est encore stocké pour le type de noisette
			// on positionne la valeur du md5 stocké à chaine vide.
			// De cette façon, on force la lecture du fichier YAML du type de noisette.
			$md5_stocke = (isset($signatures[$type_noisette]) and !$recharger)
				? $signatures[$type_noisette]
				: '';

			// Initialisation de la description par défaut du type de noisette
			// -- on y inclut le plugin appelant et la signature
			$description_defaut = array(
				'type_noisette' => $type_noisette,
				'nom'           => $type_noisette,
				'description'   => '',
				'icon'          => 'noisette-24.png',
				'necessite'     => array(),
				'actif'         => 'oui',
				'conteneur'     => 'non',
				'contexte'      => array(),
				'ajax'          => 'defaut',
				'inclusion'     => 'defaut',
				'parametres'    => array(),
				'plugin'        => $plugin,
				'signature'     => '',
			);

			// On vérifie que le md5 du fichier YAML est bien différent de celui stocké avant de charger
			// le contenu. Sinon, on passe au fichier suivant.
			$md5 = md5_file($_chemin);
			if ($md5 != $md5_stocke) {
				include_spip('inc/yaml');
				$description = yaml_decode_file($_chemin, array('include' => true));

				// TODO : ne faudrait-il pas "valider" le fichier YAML ici ou alors lors du stockage ?
				// Traitements des champs pouvant être soit une chaine soit un tableau
				if (!empty($description['necessite']) and is_string($description['necessite'])) {
					$description['necessite'] = array($description['necessite']);
				}
				if (!empty($description['contexte']) and is_string($description['contexte'])) {
					$description['contexte'] = array($description['contexte']);
				}

				// On repère les types de noisette qui nécessitent des plugins explicitement dans leur
				// fichier de configuration :
				// -- si un plugin nécessité est inactif, on indique le type de noisette comme inactif mais on l'inclut
				//    dans la liste retournée.
				// Rappel: si un type de noisette est incluse dans un plugin non actif elle ne sera pas détectée
				//         lors du find_all_in_path() puisque le plugin n'est pas dans le path SPIP.
				//         Ce n'est pas ce cas qui est traité ici.
				if (!empty($description['necessite'])) {
					foreach ($description['necessite'] as $_plugin_necessite) {
						if (!defined('_DIR_PLUGIN_' . strtoupper($_plugin_necessite))) {
							$description['actif'] = 'non';
							break;
						}
					}
				}

				// Mise à jour du md5
				$description['signature'] = $md5;
				// Complétude de la description avec les valeurs par défaut
				$description = array_merge($description_defaut, $description);
				// Traitement spécifique d'un type de noisette conteneur : l'ajax et l'inclusion dynamique 
				// ne sont pas autorisés et le contexte est défini lors de l'encapsulation.
				if ($description['conteneur'] == 'oui') {
					$description['contexte'] = array('aucun');
					$description['ajax'] = 'non';
					$description['inclusion'] = 'statique';
				}
				// Si le contexte est vide alors on le force à env pour éviter de traiter ce cas (contexte vide)
				// lors de la compilation.
				if (!$description['contexte']) {
					$description['contexte'] = array('env');
				}
				// Sérialisation des champs 'necessite', 'contexte' et 'parametres' qui sont des tableaux
				$description['necessite'] = serialize($description['necessite']);
				$description['contexte'] = serialize($description['contexte']);
				$description['parametres'] = serialize($description['parametres']);
				// Complément spécifique au plugin utilisateur si nécessaire
				$description = ncore_type_noisette_completer($plugin, $description, $stockage);

				if (!$md5_stocke or $recharger) {
					// Le type de noisette est soit nouveau soit on est en mode rechargement forcé:
					// => il faut le rajouter.
					$controles_a_ajouter[] = $description;
				} else {
					// La description stockée a été modifiée et le mode ne force pas le rechargement:
					// => il faut mettre à jour le type de noisette.
					$controles_a_changer[] = $description;
					// => et il faut donc le supprimer de la liste de types de noisette obsolètes
					$controles_a_effacer = array_diff($controles_a_effacer, array($type_noisette));
				}
			} else {
				// Le type de noisette n'a pas changé et n'a donc pas été rechargé:
				// => Il faut donc juste indiquer qu'il n'est pas obsolète.
				$controles_a_effacer = array_diff($controles_a_effacer, array($type_noisette));
			}
		}

		// Mise à jour du stockage des types de noisette si au moins un des 3 tableaux est non vide et que le chargement forcé
		// n'est pas demandé ou si le chargement forcé a été demandé:
		// -- Suppression des types de noisettes obsolètes ou de tous les types de noisettes si on est en mode rechargement forcé.
		//    Pour permettre une optimisation du traitement en mode rechargement forcé on passe toujours le mode.
		// -- Update des types de noisette modifiés.
		// -- Insertion des nouveaux types de noisette.
		if ($recharger
		or (!$recharger and ($controles_a_ajouter or $controles_a_effacer or $controles_a_changer))) {
			$controles = array('a_ajouter' => $controles_a_ajouter);
			if (!$recharger) {
				$controles['a_effacer'] = $controles_a_effacer;
				$controles['a_changer'] = $controles_a_changer;
			}
			$retour = ncore_type_noisette_stocker($plugin, $controles, $recharger, $stockage);
		}
	}

	return $retour;
}
