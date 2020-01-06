<?php

// Sécurité
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Ajouter du contenu au centre de la page sur les pages privées.
 *
 * Page d'adminisration des plugin :
 * - on recharge les types de contrôle.
 * - on recharge les dashboards
 *
 * @param $flux
 *
 * @return mixed
 */
function ezcheck_affiche_milieu($flux) {

	if (isset($flux['args']['exec'])) {
		// Initialisation de la page du privé
		$exec = $flux['args']['exec'];

		if ($exec == 'admin_plugin') {
			// Administration des plugins

			// On recharge les types de contrôles dont la liste a pu changer. Inutile de forcer un
			// rechargement complet.
			include_spip('inc/ezcheck_type_controle');
			type_controle_charger();

			// On recharge les dashboards dont la liste et le contenu ont pu changer. Inutile de forcer un
			// rechargement complet.
			include_spip('inc/ezcheck_dashboard');
			dashboard_charger();
		}
	}

	return $flux;
}
