<?php 

$tpl = eZTemplate::factory();
$result = '';
$module = $Params[ 'Module' ];
$formats = array( 'php', 'json', 'csv' );
$field_list = array();
$data = array(
    'limit' => 10,
    'offset' => 0,
    'query'  => '',
    'format' => 'php',
    'fields' => array(),
);

if( isset( $_REQUEST[ 'submit' ] ) )
{
    $solr = new eZSolrBase();

    $data = array_replace_recursive( $data, $_REQUEST[ 'data' ] );

    $format = 'php';

    $params = array(
        'q' => '*',
        'fl' => '*',
    );

    if( $data[ 'query' ] )
    {
        $params[ 'q' ] = $data[ 'query' ];
    }
    if( in_array( $data[ 'format' ], $formats ) )
    {
        $format = $data[ 'format' ];
    }
    if( $format == 'csv' && $params[ 'q' ] && $data[ 'fields' ] )
    {
        $params[ 'fl' ] = $data[ 'fields' ];
    }
    if( $data[ 'limit' ] )
    {
        $params[ 'rows' ] = $data[ 'limit' ];
    }
    if( $data[ 'offset' ] )
    {
        $params[ 'start' ] = $data[ 'limit' ];
    }
    if( $data[ 'sort' ] )
    {
        $params[ 'sort' ] = $data[ 'sort' ];
    }

    $result = $solr->rawSearch( $params );

    $result = print_r( formatData( $result, $format, $data[ 'fields' ] ), true );
}

$tpl->setVariable( 'data', $data );
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
            $resultData = $data[ 'response' ][ 'docs' ];

			$csvData = fopen('php://temp/maxmemory:'. (5*1024*1024), 'r+');
			fputcsv( $csvData, $fields );

            foreach( $resultData as $row )
            {
            	$csvRow = array();
            	foreach( $fields as $id )
				{
					$value = $row[ $id ];
					// flatten arrays
					if( is_array( $value ) )
					{
						$value = implode( '::', $value );
					}
					$csvRow[] = $value;
				}

				fputcsv( $csvData, $csvRow );
            }

            // reset pointer
			rewind( $csvData );

			$data = stream_get_contents( $csvData );
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
