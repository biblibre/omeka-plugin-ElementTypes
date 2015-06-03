# omeka-plugin-ElementTypes

Allow elements to have a type, thus allowing easier input. For instance, this plugin implements the 'date' type and show a datepicker widget for elements of this type. Other types can be implemented by plugins.

# Quick start

* Install ElementTypes and Date plugins
* A new link 'Element Types' appears on navigation menu, click on it.
* Say you want to assign the type 'date' to the 'Date' element, click on "Modify" on the corresponding row, select "Date", then Save.
* Optionally, you can configure the 'date' type for this particular element by clicking on 'Configure' link (for now you can only configure the date format)
* Edit or create an item, and see you now have a datepicker for the Date element.

## New plugins for other needs

You can create as many as plugins as you want for your specific needs and new types. For example we created one to suggest Koha authorities when you enter text. We are developping another omeka plugin to allow to define taxonomies and declare an element type 'taxonomy-term' (https://github.com/biblibre/omeka-plugin-Taxonomy).
