<script language="JavaScript1.2" type="text/javascript">
menuArray['eZFind'] = new Array();
menuArray['eZFind']['depth'] = 1; // this is a first level submenu of ContextMenu
menuArray['eZFind']['elements'] = new Array();
</script>

 <hr/>
    <a id="menu-ezfind" class="more" href="#" onmouseover="ezpopmenu_showSubLevel( event, 'eZFind', 'menu-ezfind' ); return false;">{'eZ Find'|i18n( 'extension/ezfind/popupmenu' )}</a>

{* Elevate object *}
<form id="ezfind-menu-form-elevate" method="post" action={"/ezfind/elevate/"|ezurl}>
  <input type="hidden" name="ObjectIDFromMenu" value="%objectID%" />
</form>

{* Elevation detail for object *}
<form id="ezfind-menu-form-elevation-detail" method="post" action={"/ezfind/elevation_detail/"|ezurl}>
  <input type="hidden" name="ObjectID" value="%objectID%" />
</form>