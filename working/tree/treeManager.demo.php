<h1>treeManager class showcase</h1>
<a href="class.treeManager.phps">class sourcecode</a>
<br><br>
<pre>
What is it?
===========
              Something I got tired of doing over and over again.
              This class which makes life easier when it comes to storing/retrieving hierarchical 
              arrays (trees) from/to the database. When you use this, you will be saved from 
              lots of recursive mayhem ;]

              the idea is to do two-way conversion of tree's and indented lists easily.
              Why? So you can easily store the structure in the database, and easily render
              this structure into a html selectionbox/menus etc. Think of it as some kind of 
              'serialize/unserialize' function, but with having the search-benefits of SQL.
              I hope you have fun slappin those trees :]

Usage example
=============
              Suppose your sql table layout is :

              [ id(int11) | parent_id(int11) | weight(int11) | title_menu | content(text ]

                  $treeManager     = treeManager::get();
                  $records         = $db->getArrayFromSql( "SELECT * FROM mytable" );
                  // here we have our multidimensional, weightsorted tree!
                  $recordsTree     = $treeManager->getTree( $records );
                  // here we have this tree slapped into a one-dimensional indented list
                  $recordsSlapped  = $treeManager->slapTree( $records );
                  // and vice versa!
                  $recordsTree     = $treeManager->getTree( $recordsSlapped );
                  // or wait, lets view our tree in text/html!
                  foreach( $recordsSlapped as $node )
                    echo "{$node['title_menu_indent']}\n";

Live Example
============

              suppose this is our datastructure (from SQL/PHP) :

              $records      = array(  0 => array(  "id"        => 23,
                                                   "parent_id" => 0,
                                                   "title_menu" => "root A",
                                                   "weight"    => 1 ),
                                      5 => array(  "id"        => 24,
                                                   "parent_id" => 23,
                                                   "title_menu" => "child 2",
                                                   "weight"    => 3 ),
                                      2 => array(  "id"        => 25,
                                                   "parent_id" => 0,
                                                   "title_menu" => "root B",
                                                   "weight"    => 1 ),
                                      3 => array(  "id"        => 26,
                                                   "parent_id" => 25,
                                                   "title_menu" => "child Y",
                                                   "weight"    => 1 ),
                                      4 => array(  "id"        => 27,
                                                   "parent_id" => 26,
                                                   "title_menu" => "child Z",
                                                   "weight"    => 1 ),
                                      1 => array(  "id"        => 24,
                                                   "parent_id" => 23,
                                                   "title_menu" => "child 1",
                                                   "weight"    => 1 )
                                    );
<?
function _assert( $expr, $msg){ if( !$expr ) print "<b>ASSERTION FAIL: </b>{$msg}<br>";  }
require_once( "class.treeManager.php" );

$records      = array(  0 => array(  "id"        => 23,
                                     "parent_id" => 0,
                                     "title_menu" => "root A",
                                     "weight"    => 1 ),
                        5 => array(  "id"        => 24,
                                     "parent_id" => 23,
                                     "title_menu" => "child 2",
                                     "weight"    => 3 ),
                        2 => array(  "id"        => 25,
                                     "parent_id" => 0,
                                     "title_menu" => "root B",
                                     "weight"    => 1 ),
                        3 => array(  "id"        => 26,
                                     "parent_id" => 25,
                                     "title_menu" => "child Y",
                                     "weight"    => 1 ),
                        4 => array(  "id"        => 27,
                                     "parent_id" => 26,
                                     "title_menu" => "child Z",
                                     "weight"    => 1 ),
                        1 => array(  "id"        => 24,
                                     "parent_id" => 23,
                                     "title_menu" => "child 1",
                                     "weight"    => 1 )
                      );
                                      
// here we have our tree!
$treeManager      = treeManager::get();
$recordsTree  = $treeManager->getTree( $records );

print "<PRE>// convert to multidimensional weight-sorted array<br>\$recordsTree    = getTree( \$records )<br>print_r(\$recordsTree);<hr>";
print "<h2>Output:</h2>";
print_r($recordsTree);
print "<br><br>";

// here we have our tree slapped to a indented list
$recordsSlapped  = $treeManager->slapTree( $recordsTree );
print "<PRE><hr>// convert to indented onedimensional weight-sorted array<br>\$recordsSlapped = slapTree( \$recordsTree);<br>foreach(\$recordsSlapped as \$record )<br>&nbsp;&nbsp;echo \$record['menu_title_indent'] . '\\n';<br>print_r(\$recordsSlapped);<hr>"; 
print "<h2>Output:</h2>";
foreach( $recordsSlapped as $record ) 
  echo "{$record['title_menu_indent']}\n";
print "<br>";
print_r($recordsSlapped);
print "<br><br>";

// and vice versa!
$recordsTree  = $treeManager->getTree( \$recordsSlapped );
print "<PRE><hr>// and vice versa!<br>\$recordsTree     = getTree( $recordsSlapped )<br>print_r(\$recordsTree);<hr>";
print "<h2>Output:</h2>";
print_r($recordsTree);
print "<br><br>";

?>
