<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Pagination Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used by the paginator library to build
    | the simple pagination links. You are free to change them to anything
    | you want to customize your views to better match your application.
    |
    */

    /*
     * Geral
     */

    'global_author' => 'LookCrim Team',
    'title_pt'=>'Portuguese Title',
    'title_en'=>'English Title',
    'title' => 'Title',
    'content_pt'=>'Portuguese Content',
    'content_en'=>'English Content',
    'content' => 'Content',
    'event_pt'=>'Portuguese Event Nº',
    'event_en'=>'English Event Inglês Nº',
    'highlight_pt'=>'Portuguese Highlight Nº',
    'highlight_en'=>'English Highlight Nº',
    'center-text_pt'=>'Portuguese Central Text Nº',
    'center-text_en'=>'English Central Text Nº',

    'private' => 'Private',
    'highlight' => 'Highlight',


    'notification' => 'Notification',

    // Empty states
    'empty-page' => 'No registers to show yet.',
    'empty-page-cta' => 'Create your first register using the button below.',


    /*
     * Error Pages
     */

    'error404' => 'Error 404',
    'error404-message' => 'Sorry, the page you are looking for could not be found.',



    /*
     * Admin - Users Management
     */

    'management_title' => 'Users Management',
    'management_subtitle' => '',
    'name' => 'Name',
    'verified_account' => 'Verified Account',
    'yes' => 'Yes',
    'no' => 'No',
    'date-created' => 'Creation Date',
    'type' => 'User Type',
    'account-state' => 'Account State',
    'action' => 'Edit',

    'common-user' => 'Common',
    'admin' => 'Admnistrator',
    'available' => 'Available',
    'banned' => 'Banned',


    /*
     * Admin - Users Registers Management
     */

    'registrations-management_title' => 'User Registrations Management',
    'email-verified-at' => 'E-Mail Verified At',
    'current-token' => 'Current Token',



    // --------------------



    /*
     * Homepage
     */

     'events' => 'Events',
     'highlights' => 'Highlights',
     'edit-homepage-title' => 'Edit Project Content',

    // Landing (public)
    'landing_private_platform' => 'Private platform of the Permanent Observatory of Violence and Crime (LookCrim).',
    // Categories for publications map/form
    'robo' => 'Robbery',
    'poco_iluminacion' => 'Poor lighting',
    'zona_insegura' => 'Unsafe area',
    'zona_transitada' => 'Busy area',
    'construccion' => 'Construction',
    'otro' => 'Other',
    'categories' => 'Categories',
    'category' => 'Category',
    'map_title' => 'Map of Registers',
    'radius_km' => 'Radius (km)',
    'types' => 'Types',
    'select_all' => 'Select all',
    'search_in_map_view' => 'Search in current map view',
    'search_by_radius' => 'Search by radius',
    'use_my_location' => 'Use my current location on open',
    'apply' => 'Apply',
    'clear' => 'Clear',
    'you_are_here' => 'You are here',
    'confirm_use_location' => 'Allow using your location to center the map?',
    'searching' => 'Searching...',
    'no_publications' => 'No publications to show',
    'error_network' => 'Error (network)',
    'results_suffix' => 'results',
    'porto' => 'Porto',
    'braga' => 'Braga',
    'publication' => 'Publication',
    'server_error' => 'Server error',
    'select_location' => 'Select location',
    'view_list' => 'List',
    'view_map' => 'Map',
    'view_toggle_aria' => 'Toggle view between list and map',
    'select_location_mode' => 'Click on the map to choose the center',
    'users' => 'Users',
    'all_users' => 'All users',
    'filter_by_time' => 'Filter by time',
    'from_date' => 'From',
    'to_date' => 'To',

    // Settings / Roles
    'page_settings' => 'ROLE SETTINGS',
    'roles' => 'Roles',
    'permissions' => 'Permissions',
    'name' => 'Name',
    'actions' => 'Actions',
    'no_permissions' => 'No permissions',
    'edit' => 'Edit',
    'no_roles_defined' => 'No roles defined',
    'back' => 'Back',
    'edit_role' => 'Edit Role',
    'name_en' => 'Name (EN)',
    'name_pt' => 'Name (PT)',
    'save' => 'Save',
    'role_updated_successfully' => 'Role updated successfully',
    'create_role' => 'Create Role',
    'role_created' => 'Role created successfully',
    'role_deleted' => 'Role deleted successfully',
    'delete' => 'Delete',
    'confirm_delete_role' => 'Are you sure you want to delete this role?',
    'cannot_delete_role_in_use' => 'Cannot delete role: users are assigned to it',
    'cannot_modify_protected_role' => 'This role is protected and cannot be edited or deleted.',
    'protected_role' => 'Protected',
    'slug' => 'Slug',
    'create' => 'Create',
    'role_name' => 'Role name',
    'permissions_from_role' => 'Permissions are defined by the selected role and cannot be edited per user.',

    // Settings / Cities
    'city_settings' => 'CITY SETTINGS',
    'city_settings_title' => 'CITY SETTINGS',
    'no_cities_defined' => 'No cities defined',
    'create_city' => 'Create City',
    'edit_city' => 'Edit City',
    'confirm_delete_city' => 'Are you sure you want to delete this city?',
    'cannot_delete_city_in_use' => 'Cannot delete city: users are assigned to it',
    'city_created' => 'City created successfully',
    'city_updated' => 'City updated successfully',
    'city_deleted' => 'City deleted successfully',
    'city_name' => 'City name',
    'city_center' => 'Center',
    'city_center_help' => 'Click on the map to set the center.',
    'city_radius_km' => 'Radius (km)',
    'city_radius_help' => 'The city area is a circle with this radius.',
    'cancel' => 'Cancel',

    // No access (authenticated but no permissions)
    'no_permissions_title' => 'No access',
    'no_permissions_message' => 'Your account is active, but you do not have permissions to access any sections yet.',
    'no_permissions_contact' => 'Please contact an administrator to request access.',
    'no_permissions_hint' => 'You can open your profile from the menu (top right), or log out.',

    'no_access_title' => 'No access',
    'no_access_message' => 'Your account is active, but you do not have permission to access this section.',
    'no_access_contact' => 'Please contact an administrator to request access.',

    // Registers: map point restriction
    'register_point_outside_city_blocked' => 'The selected point is outside your city area. Please choose a point inside the allowed area.',
    'register_point_outside_city_allowed' => 'The selected point is outside your city area, but it is allowed by your permissions.',
];
