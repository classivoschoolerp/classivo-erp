<?php

return [
    'navigation_group' => 'Fees Collection',
    'fee_type' => 'Fee type',
    'fee_group' => 'Fee group',
    'fee_master' => 'Fee master',
    'concession' => 'Concession',
    'receipt' => 'Receipt',
    'refund' => 'Refund',
    'credit_note' => 'Credit note',
    'offline_payment' => 'Offline payment',
    'amount' => 'Amount',
    'due_date' => 'Due date',
    'paid' => 'Paid',
    'balance' => 'Balance',
    'fine' => 'Late fine',
    'statuses' => [
        'unpaid' => 'Unpaid',
        'partial' => 'Partly paid',
        'paid' => 'Paid',
    ],
    'modes' => [
        'cash' => 'Cash',
        'cheque' => 'Cheque',
        'dd' => 'Demand draft',
        'bank_transfer' => 'Bank transfer',
        'upi' => 'UPI',
        'card' => 'Card',
    ],
    'reminder' => [
        'subject' => 'Fee reminder — :student',
        'body' => 'Fee of :amount for :student is :days days overdue.',
    ],
];
