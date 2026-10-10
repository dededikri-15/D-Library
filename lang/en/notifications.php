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
    'description' => 'Updates about borrowing, returning, overdue loans, and your own actions.',
    'menu_label' => 'Notifications',
    'bell_label' => 'Open notifications menu',
    'bell_unread' => ':count unread notification|:count unread notifications',
    'bell_none' => 'nothing unread',
    'view_all' => 'View all notifications',
    'mark_all' => 'Mark all as read',
    'mark_all_done' => 'All notifications marked as read.',
    'unread_count' => ':count unread notification|:count unread notifications',
    'unread_marker' => 'unread',
    'empty_short' => 'No notifications yet',
    'empty_title' => 'No notifications yet',
    'empty_description' => 'Borrowing, return, overdue, and action-trail updates will appear here.',
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

        /*
        | Action trail (ActionLogged): sent to the person who performed the
        | action whenever they add, edit, or delete data. The `:subject`
        | placeholder holds the name of the affected record.
        */
        'profile_updated' => [
            'title' => 'Profile updated',
            'body' => 'Your profile details were updated.',
        ],
        'password_changed' => [
            'title' => 'Password changed',
            'body' => 'Your account password was changed successfully.',
        ],
        'avatar_replaced' => [
            'title' => 'Profile photo updated',
            'body' => 'Your profile photo was updated successfully.',
        ],
        'avatar_removed' => [
            'title' => 'Profile photo removed',
            'body' => 'Your profile photo was removed. The avatar now follows your gender.',
        ],
        'book_created' => [
            'title' => 'Book added',
            'body' => 'The book :subject was added to the catalog.',
        ],
        'book_updated' => [
            'title' => 'Book updated',
            'body' => 'Changes to the book :subject were saved.',
        ],
        'book_deleted' => [
            'title' => 'Book deleted',
            'body' => 'The book :subject was removed from the catalog.',
        ],
        'category_created' => [
            'title' => 'Category added',
            'body' => 'The category :subject was added.',
        ],
        'category_updated' => [
            'title' => 'Category updated',
            'body' => 'Changes to the category :subject were saved.',
        ],
        'category_deleted' => [
            'title' => 'Category deleted',
            'body' => 'The category :subject was deleted.',
        ],
        'author_created' => [
            'title' => 'Author added',
            'body' => 'The author :subject was added.',
        ],
        'author_updated' => [
            'title' => 'Author updated',
            'body' => 'Changes to the author :subject were saved.',
        ],
        'author_deleted' => [
            'title' => 'Author deleted',
            'body' => 'The author :subject was deleted.',
        ],
        'publisher_created' => [
            'title' => 'Publisher added',
            'body' => 'The publisher :subject was added.',
        ],
        'publisher_updated' => [
            'title' => 'Publisher updated',
            'body' => 'Changes to the publisher :subject were saved.',
        ],
        'publisher_deleted' => [
            'title' => 'Publisher deleted',
            'body' => 'The publisher :subject was deleted.',
        ],
        'user_created' => [
            'title' => 'User added',
            'body' => 'The user :subject was added.',
        ],
        'user_updated' => [
            'title' => 'User updated',
            'body' => 'Changes to the user :subject were saved.',
        ],
        'user_deleted' => [
            'title' => 'User deleted',
            'body' => 'The user :subject was deleted.',
        ],
        'favorite_added' => [
            'title' => 'Added to favorites',
            'body' => ':subject was added to your favorites.',
        ],
        'favorite_removed' => [
            'title' => 'Removed from favorites',
            'body' => ':subject was removed from your favorites.',
        ],
        'reading_saved' => [
            'title' => 'Reading position saved',
            'body' => 'Your reading position for :subject was saved at page :last_page.',
        ],
        'reading_removed' => [
            'title' => 'Reading history deleted',
            'body' => 'The reading history for :subject was deleted.',
        ],
        'loan_recorded' => [
            'title' => 'Loan recorded',
            'body' => 'You recorded a loan of :subject for :user_name.',
        ],
        'loan_renewed' => [
            'title' => 'Loan renewed',
            'body' => 'The due date for :subject was extended to :due_at.',
        ],
        'return_recorded' => [
            'title' => 'Return recorded',
            'body' => 'The return of :subject was recorded on :returned_at.',
        ],
        'return_requested_self' => [
            'title' => 'Return request sent',
            'body' => 'Your request to return :subject was sent to the librarian (due :due_at).',
        ],
        'fine_paid' => [
            'title' => 'Fine marked as paid',
            'body' => 'The fine for :subject (Rp :fine) was marked as paid.',
        ],
        'loan_record_deleted' => [
            'title' => 'Loan record deleted',
            'body' => 'The loan record for :subject was deleted.',
        ],
    ],
];
