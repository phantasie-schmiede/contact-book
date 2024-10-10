plugin {
	tx_psbcontacts {
		persistence {
			storagePid = {$plugin.tx_psbcontacts.persistence.storagePid}
		}
	}

	tx_psbcontacts_contactgroups.view.defaultPid = auto
	tx_psbcontacts_contactlist.view.defaultPid = auto
}

module.tx_psbcontacts.persistence.storagePid = {$plugin.tx_psbcontacts.persistence.storagePid}
