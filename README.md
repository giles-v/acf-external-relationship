# ACFv4 External Relationship Field

This is an extension for [Advanced Custom Fields v4](http://www.advancedcustomfields.com/) allowing you to link to a list of external database resources, similar to how the [Relationship Field](http://www.advancedcustomfields.com/resources/field-types/relationship/) allows you to cross-link to Posts.

I created this field type to link products in a different CMS into posts in WordPress.

## How to use

Drop the directory into your theme folder, and link it via the functions.php file:

```
function register_acf_custom_fields() {
	include_once(dirname(__FILE__) . '/acf-external-relationship/external_relationship.php');
}
add_action('acf/register_fields', 'register_acf_custom_fields');
```

Once invoked, when adding it to a Field Group you'll need to define optional DB connection data, and three SQL queries:

__External Databases:__ 
* If the resources exist in a separate database to Wordpress, you can specify SQL connection data in the fields marked "Use External Database". By default, the system will use `$wpdb`.

__Data Queries:__
* A query for "Get All Items" which selects an ID column and a Title column (though they don't need to be called ID & Title)
* A query for searching which selects the same columns with a WHERE clause
* A query for retrieving Title from ID.

That's it.
