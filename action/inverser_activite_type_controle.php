<?php
/**
 * Ce fichier contient l'action `recharger_controles` lancée par un utilisateur pour
 * recharger le fichier de configuration de chaque contrôle de façon sécurisée.
 */

if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Cette action permet à l'utilisateur de recharger en base de données, de façon sécurisée,
 * les types de noisette à partir de leur fichier JSON.
 *
 * Cette action est réservée aux utilisateurs pouvant utiliser le noiZetier.
 * Elle ne nécessite aucun argument.
 *
 * @return void
 */
function action_inverser_activite_type_controle_dist() {

	// Sécurisation.
	// Arguments attendus :
	// - l'identifiant du type de contrôle
	// - l'état d'activité courant
	$securiser_action = charger_fonction('securiser_action', 'inc');
	$arguments = $securiser_action();

	// Verification des autorisations : pour modifier l'activité d'un type de contrôle il suffit
	// d'avoir l'autorisation minimale d'accéder au contrôles de contrib.
	if (!autoriser('webmestre')) {
		include_spip('inc/minipres');
		echo minipres();
		exit();
	}

	// Récupération des arguments
	list($type_controle, $est_actif) = explode(':', $arguments);

	// On inverse l'état courant du type de contrôle
	if (
		$type_controle
		and $est_actif
	) {
		$set = array(
			'actif' => $est_actif == 'oui' ? 'non' : 'oui'
		);
		sql_updateq('spip_types_controles', $set, 'type_controle=' . sql_quote($type_controle));
	}
}
