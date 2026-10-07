<?php

/*
| In-app notifications (topbar bell + history page).

| Texts are read when a notification is DISPLAYED (not when it is created),
| so a notification that arrived while the user was using Indonesian also
| reads in English after they switch locale — no message is stored twice in
| the database. See `components/notification-item.blade.php` for how the
| placeholders are filled from the payload.
*/

return [
    'title' => 'Notifications',
    'description' => 'Updates about borrowing, returning, and overdue loans.',
    'menu_label' => 'Notifications',
    'bell_label' => 'Open notifications menu',
    'bell_unread' => ':count unread notifications',
    'bell_none' => 'nothing unread',
    'view_all' => 'View all notifications',
    'mark_all' => 'Mark all as read',
    'mark_all_done' => 'All notifications marked as read.',
    'unread_count' => ':count unread notification|:count unread notifications',
    'unread_marker' => 'unread',
    'empty_short' => 'No notifications yet',
    'empty_title' => 'No notifications yet',
    'empty_description' => 'Borrowing, return, and overdue updates will appear here.',
    'generic_title' => 'New notification',
    'fine_notice' => 'Current fine: Rp :fine.',

    'reasons' => [
        'admin_deleted' => 'the record was removed by a librarian',
        'unknown' => 'no reason recorded',
    ],

    /*
    | One title and one sentence per notification type. Placeholders such as
    | `:name` are filled from the payload — dates already formatted, fines
    | already grouped, and reasons already translated from their code.
    */
    'types' => [
        'borrowed' => [
            'title' => 'Book borrowed',
            'body' => 'You borrowed :book_title. Return it before :due_at.',
        ],
        'loan_created' => [
            'title' => 'New loan recorded',
            'body' => ':user_name borrowed :book_title on :borrowed_at, due :due_at.',
        ],
        'approved' => [
            'title' => 'Loan approved',
            'body' => 'Your loan of :book_title was recorded on :borrowed_at. Return it before :due_at.',
        ],
        'rejected' => [
            'title' => 'Loan cancelled',
            'body' => 'Your loan of :book_title was cancelled. Reason: :reason.',
        ],
        'return_requested' => [
            'title' => 'Return requested',
            'body' => ':user_name requested to return :book_title (due :due_at).',
        ],
        'returned' => [
            'title' => 'Book returned',
            'body' => ':book_title was returned on :returned_at.',
            'fine_notice' => 'A fine of Rp :fine was recorded with this return.',
        ],
        'due_soon' => [
            'title' => 'Due date approaching',
            'body' => ':book_title is due on :due_at.',
            'renewals' => 'Renewals remaining: :renewals_left.',
        ],
        'overdue_member' => [
            'title' => 'Loan overdue',
            'body' => ':book_title is :overdue_days days overdue (was due :due_at).',
            'fine_notice' => 'Current fine: Rp :fine.',
        ],
        'overdue_staff' => [
            'title' => 'Overdue return',
            'body' => ':user_name is :overdue_days days late returning :book_title (was due :due_at).',
            'fine_notice' => 'Current fine: Rp :fine.',
        ],
    ],
];
