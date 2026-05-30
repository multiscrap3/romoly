<?php

return [
    'title'           => 'Fund Sources',
    'add'             => 'Add Fund Source',
    'add_title'       => 'Add Fund Source',
    'edit'            => 'Edit Fund Source',
    'edit_title'      => 'Edit Fund Source',
    'all'             => 'All Fund Sources',

    'name'            => 'Account Name',
    'name_ph'         => 'E.g.: BCA Savings',
    'type'            => 'Type',
    'bank'            => 'Bank Account',
    'cash'            => 'Cash',
    'ewallet'         => 'E-Wallet',
    'card_credit'     => 'Credit Card',
    'investment'      => 'Investment',
    'other'           => 'Other',
    'account_number'  => 'Account Number',
    'bank_name'       => 'Bank / Issuer Name',
    'initial_balance' => 'Initial Balance',
    'current_balance' => 'Current Balance',
    'notes'           => 'Balance can only be changed through transactions.',
    'color'           => 'Color',
    'icon'            => 'Icon',
    'icon_pick'       => 'Pick Icon',

    'save'            => 'Save',
    'cancel'          => 'Cancel',
    'delete_title'    => 'Delete Fund Source?',
    'delete_confirm'  => 'Delete this fund source?',
    'no_sources'      => 'No fund sources yet.',
    'no_active'       => 'No active fund sources yet.',

    'total_aset'      => 'Total Assets',
    'active'          => 'Active',
    'archived'        => 'Archived',
    'archive_section' => 'Archived Fund Sources',

    'deactivate'      => 'Archive',
    'deactivate_hint' => 'Archive to hide from active list. Transaction history is preserved.',
    'activate'        => 'Restore',

    // Success messages
    'stored'          => 'Fund source added successfully.',
    'updated'         => 'Fund source updated successfully.',
    'deleted'         => 'Fund source deleted successfully.',
    'deactivated'     => 'Fund source archived. Transaction history is preserved.',
    'activated'       => 'Fund source restored successfully.',
    'saldo_adjusted'  => 'Balance adjusted successfully.',

    // Error messages
    'error_has_saldo'     => 'Cannot delete — balance is still :saldo. Clear the balance through transactions first.',
    'error_has_transaksi' => 'Cannot delete because it has :count transactions. Use "Archive" to hide from active list without deleting history.',
];
