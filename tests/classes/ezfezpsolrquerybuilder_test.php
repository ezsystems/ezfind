<?php
/**
 * Test suite for ezfeZPSolrQueryBuilder
 **/
class ezfeZPSolrQueryBuilderTest extends ezpDatabaseTestCase
{
    protected $backupGlobals = false;

    /**
    * Test: providers need the "construct" parameters to be passed
    **/
//    public function __construct()
//    {
//        parent::__construct();
//        $this->setName( "ezfeZPSolrQueryBuilder Tests" );
//    }

    public function setUp()
    {
        parent::setUp();

        self::$qb = new ezfeZPSolrQueryBuilderTester();
        self::$findINI = eZINI::instance( 'ezfind.ini' );
        self::$siteINI = eZINI::instance( 'site.ini' );
    }

    /**
     * getClassAttributes test
     * @todo Manage to test attribute based list & other types (currently no
     *       other types than text in the default schema
     **/
    public function testGetClassAttributes()
    {
        // echo "DB: " . eZDB::instance()->DB . "\n";
        // xdebug_print_function_stack();

        // default parameters
        self::assertEquals(
            self::expectedGetClassAttributes(),
            self::$qb->getClassAttributes(),
            'Default parameters'
        );

        // one class by numerical class id
        self::assertEquals(
            self::$qb->getClassAttributes( 1 ),
            self::expectedGetClassAttributes( 'folder' ),
            'One numerical class id'
        );

        // one literal class identifier
        self::assertEquals(
            self::expectedGetClassAttributes( 'article' ),
            self::$qb->getClassAttributes( 'article' ),
            'One literal class identifier'
        );

        // array of literal class identifiers
        $classList = array( 'folder', 'article', 'image' );
        self::assertEquals(
            self::expectedGetClassAttributes( $classList ),
            self::$qb->getClassAttributes( $classList ),
            'Array of literal class identifiers'
        );

        // array of numerical class ids
        self::assertEquals(
            self::$qb->getClassAttributes( array( 1, 2, 3, 4, 5 ) ),
            self::expectedGetClassAttributes(
                array( 'folder', 'article', 'user_group', 'user', 'image' )
            ),
            'Array of numerical class ids' );
    }

    /**
    * fieldTypeExludeList test
    **/
    public function testFieldTypeExcludeList()
    {
        // default parameter: should exclude everything but text
        self::assertEquals(
            array( 'date', 'boolean', 'int', 'long', 'float', 'double', 'sint', 'slong', 'sfloat', 'sdouble' ),
            self::$qb->fieldTypeExludeList( null ),
            'Default parameters'
        );

        // date: should exclude everything but text & date
        self::assertEquals(
            array( 'boolean', 'int', 'long', 'float', 'double', 'sint', 'slong', 'sfloat', 'sdouble' ),
            self::$qb->fieldTypeExludeList( '2009-06-24' ),
            'Date'
        );

        // boolean: should exclude everything but text & boolean
        self::assertEquals(
            array( 'date', 'int', 'long', 'float', 'double', 'sint', 'slong', 'sfloat', 'sdouble' ),
            self::$qb->fieldTypeExludeList( 'true' ),
            'Boolean (true)'
        );
        self::assertEquals(
            array( 'date', 'int', 'long', 'float', 'double', 'sint', 'slong', 'sfloat', 'sdouble' ),
            self::$qb->fieldTypeExludeList( 'false' ),
            'Boolean (false)'
        );

        // number: should exclude booleans and dates
        self::assertEquals(
            array( 'date', 'boolean' ),
            self::$qb->fieldTypeExludeList( 10 ),
            'Number (int)'
        );
    }

    /**
    * buildLanguageFilterQuery test
    **/
    public function testBuildLanguageFilterQuery()
    {
        $languagesList = array( 'eng-GB', 'fre-FR', 'nor-NO' );

        self::$siteINI->setVariable( 'RegionalSettings', 'SiteLanguageList', $languagesList );

        // test with searchMainLanguageOnly = disabled
        self::$findINI->setVariable( 'LanguageSearch', 'SearchMainLanguageOnly', 'disabled' );
        $expectedValue = "meta_language_code_s:eng-GB OR ( meta_language_code_s:fre-FR  AND -meta_available_language_codes_s:eng-GB ) OR ( meta_language_code_s:nor-NO  AND -meta_available_language_codes_s:eng-GB AND -meta_available_language_codes_s:fre-FR )";
        $value = self::$qb->buildLanguageFilterQuery();
        self::assertEquals( $expectedValue, $value, "SearchMainLanguageOnly=disabled" );

        // test with searchMainLanguageOnly = enabled
        self::$findINI->setVariable( 'LanguageSearch', 'SearchMainLanguageOnly', 'enabled' );
        $expectedValue = "meta_language_code_s:eng-GB";
        $value = self::$qb->buildLanguageFilterQuery();
        self::assertEquals( $expectedValue, $value, "SearchMainLanguageOnly=enabled" );
    }

    /**
    * Data provider for testBuildSortParameter
    **/
    public static function providerTestBuildSortParameter()
    {
        return array(
            array( '', 'score desc' ),
            array( array( 'score' => 'asc' ),                 'score asc' ),
            array( array( 'relevance' => 'desc' ),            'score desc' ),

            array( array( 'published' => 'asc' ),             'meta_published_dt asc' ),
            array( array( 'modified' => 'asc' ),              'meta_modified_dt asc' ),
            array( array( 'class_name' => 'asc' ),            'meta_class_name_t asc' ),
            array( array( 'class_identifier' => 'asc' ),      'meta_class_identifier_s asc' ),
            array( array( 'name' => 'asc' ),                  'meta_name_t asc' ),
            array( array( 'path' => 'asc' ),                  'meta_path_si asc' ),
            array( array( 'section_id' => 'asc' ),            'meta_section_id_si asc' ),
            array( array( 'author' => 'asc' ),                'meta_owner_name_t asc' ),

            // a few attributes
            array( array( 'article/title' => 'asc' ),         ezfSolrDocumentFieldBase::ATTR_FIELD_PREFIX . 'title_t asc' ),
            array( array( 'folder/name' => 'asc' ),           ezfSolrDocumentFieldBase::ATTR_FIELD_PREFIX . 'name_t asc' ),
            array( array( 'article/body' => 'asc' ),          ezfSolrDocumentFieldBase::ATTR_FIELD_PREFIX . 'body_t asc' ),
        );
    }

    /**
     * @dataProvider providerTestBuildSortParameter
     **/
    public function testBuildSortParameter( $parameter, $expected )
    {
        self::assertEquals(
            $expected,
            self::$qb->buildSortParameter( array( 'SortBy' => $parameter ) ) );
    }

    public static function providerTestQuoteIfNeeded()
    {
        return array(
            array( 'simplestring', 'simplestring' ),
            array( 'a string with spaces', '"a string with spaces"' ),
            array( 'a string with spaces and (parenthesis)', '"a string with spaces and (parenthesis)"' ),
            array( '(a string with spaces in parenthesis)', '(a string with spaces in parenthesis)' ),
        );
    }

    /**
    * @dataProvider providerTestQuoteIfNeeded
    **/
    public function testQuoteIfNeeded( $value, $expected )
    {
        self::assertEquals( $expected, ezfeZPSolrQueryBuilder::quoteIfNeeded( $value ) );
    }

    /**
    * Returns the list of expected class attributes by class list and/or type
    * Will probably have to be refactored... bad approach.
    * @see testGetClassAttributes
    **/
    protected static function expectedGetClassAttributes( $classList = null, $typeList = 'text' )
    {
        $attributes = array(

            // folder
            'folder' => array(
                'text' => array(
                    ezfSolrDocumentFieldBase::ATTR_FIELD_PREFIX . 'description_t',
                    ezfSolrDocumentFieldBase::ATTR_FIELD_PREFIX . 'name_t',
                    ezfSolrDocumentFieldBase::ATTR_FIELD_PREFIX . 'short_description_t',
                    ezfSolrDocumentFieldBase::ATTR_FIELD_PREFIX . 'short_name_t',
                ),
            ),

            // article
            'article' => array(
                'text' => array(
                    ezfSolrDocumentFieldBase::ATTR_FIELD_PREFIX . 'body_t',
                    ezfSolrDocumentFieldBase::ATTR_FIELD_PREFIX . 'image_t',
                    ezfSolrDocumentFieldBase::ATTR_FIELD_PREFIX . 'intro_t',
                    ezfSolrDocumentFieldBase::ATTR_FIELD_PREFIX . 'short_title_t',
                    ezfSolrDocumentFieldBase::ATTR_FIELD_PREFIX . 'title_t',
                ),
            ),

            // user group
            'user_group' => array(
                'text' => array(
                    ezfSolrDocumentFieldBase::ATTR_FIELD_PREFIX . 'description_t',
                    ezfSolrDocumentFieldBase::ATTR_FIELD_PREFIX . 'name_t'
                )
            ),

            // user
            'user' => array(
                'text' => array(
                    ezfSolrDocumentFieldBase::ATTR_FIELD_PREFIX . 'first_name_t',
                    ezfSolrDocumentFieldBase::ATTR_FIELD_PREFIX . 'last_name_t',
                    ezfSolrDocumentFieldBase::ATTR_FIELD_PREFIX . 'signature_t',
                    ezfSolrDocumentFieldBase::ATTR_FIELD_PREFIX . 'user_account_t',
                ),
            ),

            // image
            'image' => array(
                'text' => array(
                    ezfSolrDocumentFieldBase::ATTR_FIELD_PREFIX . 'caption_t',
                    ezfSolrDocumentFieldBase::ATTR_FIELD_PREFIX . 'name_t',
                )
            ),

            // link
            'link' => array(
                'text' => array(
                    ezfSolrDocumentFieldBase::ATTR_FIELD_PREFIX . 'description_t',
                    ezfSolrDocumentFieldBase::ATTR_FIELD_PREFIX . 'name_t',
                ),
            ),

            // file
            'file' => array(
                'text' => array(
                    ezfSolrDocumentFieldBase::ATTR_FIELD_PREFIX . 'description_t',
                    ezfSolrDocumentFieldBase::ATTR_FIELD_PREFIX . 'name_t',
                ),
            ),

            // comment
            'comment' => array(
                'text' => array(
                    ezfSolrDocumentFieldBase::ATTR_FIELD_PREFIX . 'author_t',
                    ezfSolrDocumentFieldBase::ATTR_FIELD_PREFIX . 'message_t',
                    ezfSolrDocumentFieldBase::ATTR_FIELD_PREFIX . 'subject_t',
                ),
            ),

            // common_ini_settings
            'common_ini_settings' => array(
                'text' => array(
                    ezfSolrDocumentFieldBase::ATTR_FIELD_PREFIX . 'name_t',
                )
            ),

            // template_look
            'template_look' => array(
                'text' => array(
                    ezfSolrDocumentFieldBase::ATTR_FIELD_PREFIX . 'id_t',
                ),
            ),
        );

        if ( $classList === null )
            $classList = array_keys( $attributes );

        $result = array();
        if ( !is_array( $classList ) )
            $classList = array( $classList );
        if ( !is_array( $typeList ) )
            $typeList = array( $typeList );

        foreach ( $classList as $class )
        {
            // $typeList will always contain at least 'text'
            foreach ( $typeList as $type )
            {
                if ( !isset( $attributes[$class][$type] ) )
                    throw new Exception("Unknown class/type combination list $class/$type" );
                $result = array_merge( $result, $attributes[$class][$type] );
            }
        }
        $result = array_unique( $result );
        sort( $result );

        return $result;

    }

    /**
     * QueryBuilder tester instance
     * @var ezfeZPSolrQueryBuilderTester
     **/
    protected static $qb;

    /**
     * ezfind.ini instance
     * @var eZINI
     **/
    protected static $findINI;

    /**
     * site.ini instance
     * @var eZINI
     **/
    protected static $siteINI;
}
?>