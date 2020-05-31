# Available Hooks in Asgaros Forum

Overview of [actions](#actions) and [filters](#filters) at asgaros forum.

## Actions



## Filters

- [Header Menu](#asgarosforum_filter_header_menu)

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

#### Usage

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

[forum.php]{includes/forum.php}

