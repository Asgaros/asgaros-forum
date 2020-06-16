# Available Hooks in Asgaros Forum

Overview of [actions](#actions) and [filters](#filters) at asgaros forum.

## Actions



## Filters

- [asgarosforum_filter_header_menu](#asgarosforum_filter_header_menu)
- [asgarosforum_filter_profile_header_image](#asgarosforum_filter_profile_header_image)

### asgarosforum_filter_header_menu

#### Description
Filter the header menu of asgaros forum.

#### Parameters

##### $menu_entries

Array with Menu Entries as arrays:

```php
$menu_entries = array(
    'name' =>   array(
                    'menu_class'        =>  'HTML Class'
                    'menu_link_text'    =>  'Link Text',
                    'menu_url'          =>  '/url',
                    'menu_login_status' =>  '0',  // (0 = all, 1 = only logged in, 2 = only logged out)
                    'menu_new_tab'      =>  true  // (true = open in new tab, false = open in same tab
                ),
);
```



Names of the standard menu entries:

| name         | description                         | visibility      |
|--------------|-------------------------------------|-----------------|
| home         | Homepage of the Asgaros Forum       | Always          |
| profile      | Profile of the active member        | Only logged in  |
| memberslist  | List of all members                 | Always          |
| subscription | Page to manage subscriptions        | Only logged in  |
| activity     | Page of all activities in the forum | Always          |
| login        | Login page                          | Only logged out |
| register     | Register Page                       | Only logged out |
| logout       | Logout actual user                  | Only logged out |


#### Usage

```php
<?php
    add_filter ( 'asgarosforum_filter_header_menu', 'function_name');
?>
```

#### Examples

```php
<?php
    // Add filter to customize the forum header menu
    add_filter ( 'asgarosforum_filter_header_menu', 'my_custom_menu');

    // Function to customize the forum menu
    function my_custom_menu( $menu_entries){

        // Open memberslist in new tab
        $menu_entries['memberslist']['menu_new_tab'] = true;

        // Create new menu entry
        $menu_entry = array(
                          'menu_class'        =>  'impress',
                          'menu_link_text'    =>  'Impress',
                          'menu_url'          =>  '/impress',
                          'menu_login_status' =>  '0',
                          'menu_new_tab'      =>  true
                      );


        // Add Entry at beginning of the menu
        array_unshift( $menu_entries, $menu_entry);

        return $menu_entries;
    }
?>
```

#### Source

[forum.php](includes/forum.php)


### asgarosforum_filter_profile_header_image

#### Description
Filters the URL to the background image of the forum profile header.

#### Parameters

##### $url

URL to the background image.

##### $user_id

User ID of shown profile.

#### Usage

```php
<?php
    add_filter ( 'asgarosforum_filter_profile_header_image', 'function_name', 10, 2);
?>
```

#### Examples

```php
<?php
    // Add filter to customize a user profile header background image
    add_filter ( 'asgarosforum_filter_profile_header_image', 'custom_profile_background', 10, 2);

    // Remove profile header background if user is admin
    function custom_profile_background( $url, $user_id){

        // check if user is admin
        if ( user_can( $user_id, 'manage_options' )){
            $url = false;
        }

        return $url;
    }
?>
```

#### Source

[forum-profile.php](includes/forum-profile.php)
