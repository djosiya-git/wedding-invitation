<?php require __DIR__.'/lib.php'; $k=$_GET['template']??''; $t=templates()[$k]??null; if(!$t){http_response_code(404);exit('Template tidak ditemukan');} readfile(__DIR__.'/templates/'.$t['file']);
