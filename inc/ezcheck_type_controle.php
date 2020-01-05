<?php
/**
 * Ce fichier contient l'API de gestion des types de contrôle.
 */
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Charge ou recharge les descriptions des types de contrôle à partir des fichiers YAML/JSON.
 * La fonction optimise le chargement en effectuant uniquement les traitements nécessaires
 * en fonction des modifications, ajouts et suppressions des contrôles identifiés
 * en comparant les md5 des fichiers YAML/JSON.
 *
 * @param bool $recharger Si `true` force le rechargement de tous les types de contrôles sinon le chargement se base
 *                        sur le md5 des fichiers YAML. Par défaut vaut `false`.
 *
 * @return bool `false` si une erreur s'est produite, `true` sinon.
 *@api
 *
 */
function type_controle_charger($recharger) {

	// Retour de la fonction
	$retour = true;

	// On recherche les contrôles directement par leur fichier YAML de configuration car il est
	// obligatoire. La recherche s'effectue dans le path en utilisant le dossier relatif fourni.
	if ($fichiers = find_all_in_path('controles/', '.+[.]yaml$')) {
		// Initialisation des tableaux de types de contrôle.
		$types_controle_a_ajouter = $types_controle_a_changer = $types_controle_a_effacer = array();

		// Récupération de la description complète des contrôles déjà enregistrés de façon :
		// - à gérer l'activité des types en fin de chargement
		// - de comparer les signatures md5 des noisettes déjà enregistrées. Si on force le rechargement il est inutile
		//   de gérer les signatures et les contrôles modifiés ou obsolètes.
		$types_controle_existants = type_controle_lister();
		$signatures = array();
		if (!$recharger) {
			$signatures = array_column($types_controle_existants, 'signature', 'type_controle');
			// On initialise la liste des contrôles à supprimer avec l'ensemble des contrôles déjà stockés.
			$types_controle_a_effacer = $signatures ? array_keys($signatures) : array();
		}

		foreach ($fichiers as $_squelette => $_chemin) {
			$type_controle = basename($_squelette, '.yaml');
			// Si on a forcé le rechargement ou si aucun md5 n'est encore stocké pour le contrôle
			// on positionne la valeur du md5 stocké à chaine vide.
			// De cette façon, on force la lecture du fichier JSON/YAML du contrôle.
			$md5_stocke = (isset($signatures[$type_controle]) and !$recharger)
				? $signatures[$type_controle]
				: '';

			// Initialisation de la description par défaut du type de contrôle
			$description_defaut = array(
				'type_controle' => $type_controle,
				'fonction'      => 'php',
				'nom'           => $type_controle,
				'description'   => '',
				'icone'         => 'controle-24.png',
				'priorite'      => 0,
				'periode'       => 0,
				'actif'         => 'oui',
				'signature'     => '',
			);

			// On vérifie que le md5 du fichier JSON/YAML est bien différent de celui stocké avant de charger
			// le contenu. Sinon, on passe au fichier suivant.
			$md5 = md5_file($_chemin);
			if ($md5 != $md5_stocke) {
				// Lecture et décodage du fichier YAML en structure de données PHP.
				include_spip('inc/yaml');
				$description = yaml_decode_file($_chemin, array('include' => false));

				$description['signature'] = $md5;
				// Complétude de la description avec les valeurs par défaut
				$description = array_merge($description_defaut, $description);

				if (!$md5_stocke or $recharger) {
					// Le type de noisette est soit nouveau soit on est en mode rechargement forcé:
					// => il faut le rajouter.
					$types_controle_a_ajouter[] = $description;
				} else {
					// La description stockée a été modifiée et le mode ne force pas le rechargement:
					// => il faut mettre à jour le type de noisette.
					$types_controle_a_changer[] = $description;
					// => et il faut donc le supprimer de la liste de types de noisette obsolètes
					$types_controle_a_effacer = array_diff($types_controle_a_effacer, array($type_controle));
				}
			} else {
				// Le type de noisette n'a pas changé et n'a donc pas été rechargé:
				// => Il faut donc juste indiquer qu'il n'est pas obsolète.
				$types_controle_a_effacer = array_diff($types_controle_a_effacer, array($type_controle));
			}
		}

		// Mise à jour des contrôles en base de données :
		// -- Suppression des contrôles obsolètes ou de tous les contrôles si on est en mode rechargement forcé.
		// -- Update des contrôles modifiés.
		// -- Insertion des nouveaux contrôles.

		// Mise à jour de la table des contrôles
		$from = 'spip_types_controles';
		// -- Suppression des pages obsolètes ou de toute les pages non virtuelles si on est en mode
		//    rechargement forcé.
		if (sql_preferer_transaction()) {
			sql_demarrer_transaction();
		}
		if ($types_controle_a_effacer) {
			sql_delete($from, sql_in('type_controle', $types_controle_a_effacer));
		} elseif ($recharger) {
			sql_delete($from);
		}
		// -- Update des contrôels modifiés
		if ($types_controle_a_changer) {
			sql_replace_multi($from, $types_controle_a_changer);
		}
		// -- Insertion des nouveaux contrôles
		if ($types_controle_a_ajouter) {
			sql_insertq_multi($from, $types_controle_a_ajouter);
		}
		if (sql_preferer_transaction()) {
			sql_terminer_transaction();
		}
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
function type_controle_lister($information = '') {

	// Initialiser le tableau de sortie en cas d'erreur
	$types_controle = array();

	$from = 'spip_types_controles';
	$trouver_table = charger_fonction('trouver_table', 'base');
	$table = $trouver_table($from);
	$champs = array_keys($table['field']);
	if ($information) {
		// Si une information précise est demandée on vérifie sa validité
		$information_valide = in_array($information, $champs);
		$select = array('type_controle', $information);
	} else {
		// Tous les champs sauf le timestamp 'maj' sont renvoyés.
		$select = array_diff($champs, array('maj'));
	}

	if ((!$information or ($information and $information_valide))
	and ($types_controle = sql_allfetsel($select, $from))) {
		if ($information) {
			$types_controle = array_column($types_controle, $information, 'type_controle');
		} else {
			$types_controle = array_column($types_controle, null, 'type_controle');
		}
	}

	return $types_controle;
}
