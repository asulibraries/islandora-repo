1. make an interface which outlines a few pieces of functionality including:
    a. create functionality
    b. tombstone functionality
    c. a settings/config per plugin

2. make a field to store the identifier

3. include an rdf mapping for the field

4. make a settings which lists each of the plugins and which are enabled

5. make a setting which lists all of the entity types for which this should apply - when its saved it would have to check for the field on the entity types and create it if it isn't there

6. make a hook_entity_insert which fires all the enabled plugins in the settings

7. make a hook_entity_delete which fires all the enabled plugins in the settings
