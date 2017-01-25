<?php

/*
 * File containing example index plugin class
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 * @package ezfind
 */

/**
 * This class adds the name of the parent of the main node as de dedicated field.
 *
 */
class ezfIndexParentName implements ezfIndexPlugin
{
    /**
     * The modify method gets the current content object AND the list of
     * Solr Docs (for each available language version).
     *
     *
     * @param eZContentObject $contentObect
     * @param array $docList
     */
    public function modify( eZContentObject $contentObject, &$docList )
    {
        $contentNode = $contentObject->attribute( 'main_node' );
        $parentNode = $contentNode->attribute( 'parent' );
        if ( $parentNode instanceof eZContentObjectTreeNode )
        {
            $parentObject       = $parentNode->attribute( 'object' );
            $parentVersion      = $parentObject->currentVersion();
            if( $parentVersion === false )
            {
                return;
            }
            $availableLanguages = $parentVersion->translationList( false, false );
            foreach ( $availableLanguages as $languageCode )
            {
                $docList[$languageCode]->addField('extra_parent_node_name_t', $parentObject->name( false, $languageCode ) );
            }
        }

    }
}

?>
