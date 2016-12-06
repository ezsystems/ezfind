<?php 

$tpl = eZTemplate::factory();
$result = '';
$module = $Params[ 'Module' ];
$formats = array( 'php', 'json', 'csv' );
$field_list = array();
$fields = array(
    'limit' => 10,
    'offset' => 0,
    'query'  => '',
    'format' => 'php'
);

if( isset( $_REQUEST[ 'submit' ] ) )
{
    $solr = new eZSolrBase();

    $fields = $_REQUEST[ 'fields' ];
    $format = 'php';

    $params = array(
        'q' => '*',
        'fl' => '*',
    );

    if( $fields[ 'query' ] )
    {
        $params[ 'q' ] = $fields[ 'query' ];
    }
    if( in_array( $fields[ 'format' ], $formats ) )
    {
        $format = $fields[ 'format' ];
    }
    if( $format == 'csv' && $params[ 'q' ] && $fields[ 'fields' ] )
    {
        $params[ 'fl' ] = $fields[ 'fields' ];
        $field_list = explode( ',', $fields[ 'fields' ] );
        $field_list = array_map( 'trim', $field_list );
    }
    if( $fields[ 'limit' ] )
    {
        $params[ 'rows' ] = $fields[ 'limit' ];
    }
    if( $fields[ 'offset' ] )
    {
        $params[ 'start' ] = $fields[ 'limit' ];
    }
    if( $fields[ 'sort' ] )
    {
        $params[ 'sort' ] = $fields[ 'sort' ];
    }

    $result = $solr->rawSearch( $params );

    $result = print_r( formatData( $result, $format, $field_list ), true );
}

$tpl->setVariable( 'fields', $fields );
$tpl->setVariable( 'result', $result );

$Result = array();
$Result['content'] = $tpl->fetch( "design:ezfind/query.tpl" );
$Result['left_menu'] = "design:ezfind/backoffice_left_menu.tpl";
$Result['path'] = array( array( 'url' => false,
                                'text' => ezpI18n::tr( 'extension/ezfind', 'eZFind' ) ),
                         array( 'url' => false,
                                'text' => ezpI18n::tr( 'extension/ezfind', 'Query' ) ) );

/*
 * functions
 */

function formatData( $data, $format, $fields )
{
    switch( $format )
    {
        case 'csv':
        {
            $data = $data[ 'response' ][ 'docs' ];
            
            foreach( $data as $r => $row )
            {
                $newRowContent = array();

                if( !empty( $fields ) )
                {
                    $rowOrdered = array();
                    foreach( $fields as $field )
                    {
                        $rowOrdered[ $field ] = $row[ $field ] ? $row[ $field ] : '';
                    }
                    $row = $rowOrdered;
                }

                foreach( $row as $field )
                {
                    // multi-value fields
                    $content = is_array( $field ) ? implode( '::', $field ) : $field;

                    $newRowContent[] = '"' . str_replace( '"', '\"', $content ) . '"';
                }
                                
                $data[ $r ] = implode( ',', $newRowContent );
            }
            
            $data = implode( "\n", $data );
        }
        break;

        case 'json':
        {
            $data = json_encode( $data );
        }
        break;
    }

    return $data;
}
