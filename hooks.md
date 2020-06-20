# Available Hooks in Asgaros Forum

Overview of [actions](#actions) and [filters](#filters) at asgaros forum.

## Actions



## Filters

- [Profile Header Image](#asgarosforum_filter_profile_header_image)

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

#### Usage

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
