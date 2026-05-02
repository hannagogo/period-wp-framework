<?php
require_once 'ClassTemplate.php';
require_once 'phpQuery.php';

class HTMLScraper extends ClassTemplate {
public $document;
public $dom;
function __construct($html) {
 $this->document = $html;
 $this->setup_document($this->document);
 
}

function setup_document($html=null) {
 if (!$html) $html = $this->document;
 else $this->document = $html;
 $this->dom = phpQuery::newDocument($html);
 phpQuery::selectDocument($this->dom);
}

function select($selector, $document=null) {
 $this->setup_document($document);
 return pq($selector);
}
}