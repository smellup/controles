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
function action_recharger_types_controle_dist() {

	// Sécurisation.
	// -- Aucun argument attendu.

	// Verification des autorisations : pour recharger les types de contrôle il suffit
	// d'avoir l'autorisation minimale d'accéder au contrôles de contrib.
	if (!autoriser('webmestre')) {
		include_spip('inc/minipres');
		echo minipres();
		exit();
	}

	// Rechargement des types de contrôle : on force le recalcul complet, c'est le but.
	include_spip('inc/ezcheck_type_controle');
	type_controle_charger(true);
}
