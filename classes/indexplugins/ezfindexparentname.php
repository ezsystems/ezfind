<?php

/*
 * File containing example index plugin class
 */

/**
 * Description of ezfindexparentname
 *
 * @author paul
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
    public function modify(eZContentObject $contentObect, &$docList)
    {
        $contentNode = $contentObect->attribute('main_node');
        $parentNode = $contentNode->attribute('parent');
        if ($parentNode instanceof eZContentObjectTreeNode)
        {
            $parentObject       = $parentNode->attribute('object');
            $parentVersion      = $parentObject->currentVersion();
            $availableLanguages = $parentVersion->translationList( false, false );
            foreach ($availableLanguages as $languageCode)
            {
                $docList[$languageCode]->addField('extra_parent_node_name_t', $parentObject->name( false, $languageCode ) );
            }
        }

    }
}

?>
