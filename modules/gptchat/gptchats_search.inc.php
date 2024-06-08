<?php
/*
* @version 0.1 (wizard)
*/
 global $session;
  if ($this->owner->name=='panel') {
   $out['CONTROLPANEL']=1;
  }
  $qry="1";
  // search filters
  // QUERY READY
  global $save_qry;
  if ($save_qry) {
   $qry=$session->data['gptchats_qry'];
  } else {
   $session->data['gptchats_qry']=$qry;
  }
  if (!$qry) $qry="1";
  $sortby_gptchats="ID DESC";
  $out['SORTBY']=$sortby_gptchats;
  // SEARCH RESULTS
  $res=SQLSelect("SELECT * FROM gptchats WHERE $qry ORDER BY ".$sortby_gptchats);
  if ($res[0]['ID']) {
   //paging($res, 100, $out); // search result paging
   $total=count($res);
   for($i=0;$i<$total;$i++) {
    // some action for every record if required
   }
   $out['RESULT']=$res;
  }
