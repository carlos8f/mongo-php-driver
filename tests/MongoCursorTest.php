<?php
require_once 'PHPUnit/Framework.php';

/**
 * Test class for MongoCursor.
 * Generated by PHPUnit on 2009-04-09 at 18:09:02.
 */
class MongoCursorTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var    MongoCursor
     * @access protected
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        $this->object = $this->sharedFixture->selectCollection('phpunit', 'c');
        $this->object->drop();
        //        $this->object->start = memory_get_usage(true);
    }

    protected function tearDown() {
        //        $this->assertEquals($start, memory_get_usage(true));
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function test__construct() {
      $c = $this->object->find(null);
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function test__construct1() {
      $c = $this->object->find(null, null);
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function test__construct2() {
      $c = $this->object->find(array(), null);
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function test__construct3() {
      $c = new MongoCursor($this->sharedFixture, "foo.bar", null);
    }

    /**
     * @expectedException MongoException
     */
    public function test__construct4() {
      $this->object->findOne(array(), array("id", 123));
    }

    public function testHasNext() {
        $c = $this->object->find();
        $this->assertFalse($c->hasNext());

        $this->object->insert(array('foo' => 'bar'));
        $c = $this->object->find();
        $this->assertTrue($c->hasNext());

        $c->getNext();
        $this->assertFalse($c->hasNext());
    }

    public function testInfo()
    {
        $filter = array(
            'property1' => 'value1',
            'property2' => array('$in' => array(1,2)),
        );

        $cursor = $this->object->find($filter);
        $query  = $cursor->info();


        foreach (array('limit', 'skip', 'query', 'ns') as $property) {
            $this->assertTrue(isset($query[$property]));
        }

        $this->assertEquals($query['ns'], 'phpunit.c');
        $this->assertEquals($query['limit'], 0);
        $this->assertEquals($query['skip'], 0);
        $this->assertEquals($query['query'], $filter);
        $this->assertEquals(get_object_vars($query['fields']), array());
    }

    public function testGetNext() {
        $c = $this->object->find();
        $this->assertEquals($c->getNext(), null);

        for ($i=0; $i<10; $i++) {
            $this->object->insert(array('z' => $i));
        }

        $c = $this->object->find();
        for ($i=0; $i<10; $i++) {
            $c->getNext();
            $this->assertNotNull($c);
        }
        $this->assertEquals($c->getNext(), null);
    }

    public function testLimit() {
        for ($i=0; $i<20; $i++) {
            $this->object->insert(array('z' => $i));
        }

        $c = $this->object->find()->limit(0);
        $count = 0;
        foreach ($c as $obj) {
            $count++;
        }
        $this->assertEquals($count, 20);

        $c = $this->object->find()->limit(null);
        $count = 0;
        foreach ($c as $obj) {
            $count++;
        }
        $this->assertEquals($count, 20);

        $c = $this->object->find()->limit(5);
        $count = 0;
        foreach ($c as $obj) {
            $count++;
        }
        $this->assertEquals($count, 5);

        $c = $this->object->find()->limit(20);
        $count = 0;
        foreach ($c as $obj) {
            $count++;
        }
        $this->assertEquals($count, 20);

        $c = $this->object->find()->limit(200);
        $count = 0;
        foreach ($c as $obj) {
            $count++;
        }
        $this->assertEquals($count, 20);

        $c = $this->object->find()->limit("12");
        $count = 0;
        foreach ($c as $obj) {
            $count++;
        }
        $this->assertEquals($count, 12);
    }

    public function testSkip()
    {
        for ($i=0; $i<20; $i++) {
            $this->object->insert(array('z' => $i));
        }

        $c = $this->object->find()->sort(array('z'=>1))->skip(4);
        $count = 4;
        foreach ($c as $obj) {
            $this->assertEquals($count, $obj['z']);
            $count++;
        }
        $this->assertEquals($count, 20);

        $c = $this->object->find()->sort(array('z'=>1))->skip(0);
        $count = 0;
        foreach ($c as $obj) {
            $this->assertEquals($count, $obj['z']);
            $count++;
        }
        $this->assertEquals($count, 20);

        $c = $this->object->find()->sort(array('z'=>1))->skip(null);
        $count = 0;
        foreach ($c as $obj) {
            $this->assertEquals($count, $obj['z']);
            $count++;
        }
        $this->assertEquals($count, 20);
    }

    public function testSort() {
        for ($i=0; $i<20; $i++) {
            if ($i % 2 == 0) {
                $this->object->insert(array('z' => $i, 'x' => 'a'));
            }
            else if($i % 3 == 0) {
                $this->object->insert(array('z' => $i, 'x' => 'b'));
            }
            else {
                $this->object->insert(array('z' => $i, 'x' => 'c'));
            }
        }

        $c = $this->object->find()->sort(array('z'=>1));
        $count = 0;
        foreach ($c as $obj) {
            $this->assertEquals($count, $obj['z']);
            $count++;
        }
        $this->assertEquals($count, 20);

        $c = $this->object->find()->sort(array('x'=>1, 'z'=>-1));
        for ($i=18;$i>=0;$i-=2) {
            $obj = $c->getNext();
            $this->assertEquals($obj['z'], $i);
            $this->assertEquals($obj['x'], 'a');
        }
        $obj = $c->getNext();
        $this->assertEquals($obj['z'], 15);
        $this->assertEquals($obj['x'], 'b');
        $obj = $c->getNext();
        $this->assertEquals($obj['z'], 9);
        $this->assertEquals($obj['x'], 'b');
        $obj = $c->getNext();
        $this->assertEquals($obj['z'], 3);
        $this->assertEquals($obj['x'], 'b');

        for ($i=19;$i>=0;$i-=2) {
            if ($i % 3 == 0) {
                continue;
            }
            $obj = $c->getNext();
            $this->assertEquals($obj['z'], $i);
            $this->assertEquals($obj['x'], 'c');
        }

        $this->assertFalse($c->hasNext());
    }

    public function testHint() {
        $c = $this->object->find();
        $d = $c->hint(array('x'=>1));

        $this->assertSame($c, $d);
    }

    public function testCurrent() {
        for ($i=0; $i<20; $i++) {
            $this->object->insert(array('z' => $i));
        }

        $c = $this->object->find()->sort(array('z' => -1));
        $this->assertEquals($c->current(), null);
        for ($i=19; $i>=0; $i--) {
            $c->next();
            $obj = $c->current();
            $this->assertEquals($obj['z'], $i);
        }
        $c->next();
        $this->assertEquals($c->current(), null);
    }

    public function testKey() {
        if (preg_match($this->sharedFixture->version_51, phpversion())) {
            $this->markTestSkipped("No implicit __toString in 5.1");
            return;
        }

        for ($i=0; $i<20; $i++) {
            $this->object->insert(array('z' => $i));
        }

        $c = $this->object->find();
        foreach ($c as $k=>$v) {
            $this->assertEquals($k, (string)$v['_id']);
        }

        $ns = $this->object->db->selectCollection("system.indexes");
        $cursor = $ns->find();

        foreach ($cursor as $k => $v) {
            if (!array_key_exists('_id', $v)) {
                $this->assertEquals("", $k, json_encode($v));
            }
        }
    }

    public function testNext() {
        for ($i=0; $i<20; $i++) {
            $this->object->insert(array('z' => $i));
        }

        $c = $this->object->find()->skip(3)->sort(array('z'=>1))->limit(3);
        $c->next();
        $c->next();
        $c->next();

        $x = $c->current();
        $this->assertEquals($x['z'], 5);

        // don't crash
        $c->next();
        $this->assertEquals($c->current(), null);
        $c->next();
        $this->assertEquals($c->current(), null);
        $c->next();
        $this->assertEquals($c->current(), null);
    }

    public function testRewind() {
        for ($i=0; $i<5; $i++) {
            $this->object->insert(array('z' => $i));
        }

        $c = $this->object->find()->sort(array('z'=>1));

        $c->next();
        $x = $c->current();
        $this->assertEquals($x['z'], 0);
        $c->next();
        $x = $c->current();
        $this->assertEquals($x['z'], 1);

        $c->rewind();
        $x = $c->current();
        $this->assertEquals($x['z'], 0);
    }

    public function testValid() {
        $this->object->insert(array('z' => 0));

        $c = $this->object->find();
        $this->assertFalse($c->valid());
        $c->next();
        $this->assertTrue($c->valid());
        $c->next();
        $this->assertFalse($c->valid());
    }

    public function testReset() {
        for ($i=0; $i<5; $i++) {
            $this->object->insert(array('z' => $i));
        }

        $c = $this->object->find()->sort(array('z'=>1));
        $x = $c->current();
        $this->assertEquals($x, null);

        $c->next();
        $x = $c->current();
        $this->assertEquals($x['z'], 0);

        $c->reset();

        // this should be ok
        $c->limit(1)->skip(2);
        $obj = $c->getNext();
        $this->assertEquals($obj['z'], 2);
        $this->assertFalse($c->hasNext());
    }

    /**
     * @expectedException MongoCursorException
     */
    public function testCursorException1() {
        $c = $this->object->find();
        $c->hasNext();
        $c->limit(4);
    }


    /**
     * @expectedException MongoCursorException
     */
    public function testCursorException3() {
        $c = $this->object->find();
        $c->hasNext();
        $c->sort(array('x' => 1));
    }

    /**
     * @expectedException MongoCursorException
     */
    public function testCursorException4() {
        $c = $this->object->find();
        $c->getNext();
        $c->skip(4);
    }

    public function testCount() {
      $this->object->insert(array('x'=>1));
        $this->object->insert(array('x'=>2));
        $this->object->insert(array('x'=>3, 'y'=>1));
        
        $cursor = $this->object->find();
        $this->assertEquals(3, $cursor->count());
        
        $cursor = $this->object->find(array('x'=>1));
        $count = $cursor->count(); 
        $str = json_encode(iterator_to_array($cursor));
        $this->assertEquals(1, $count, $str);         
    }

    public function testExplain() {
        $c = $this->object->find();
        $x = $c->explain();
        $this->assertEquals(0, $x['n'], json_encode($x));

        $c->next();
        $x = $c->explain();
        $this->assertEquals(0, $x['n'], json_encode($x));
    }


    public function testReset2() {
        for ($i=0; $i<20; $i++) {
            $this->object->insert(array('x'=>$i, "b$i" => 'foo'));
        }

        $cursor = $this->object->find();
        for ($i=0; $i<20; $i++) {
            $this->assertNotNull($cursor->getNext());
        }

        $cursor->reset();
        $cursor->limit(1);

        $this->assertNotNull($cursor->getNext());
        $this->assertEquals(NULL, $cursor->getNext());

        $cursor->reset();
        $cursor->limit(0);

        for ($i=0; $i<20; $i++) {
            $this->assertNotNull($cursor->getNext());
        }
    }

    public function testResetRealloc() {
        for ($i=0; $i<20; $i++) {
            $this->object->insert(array('x'=>$i, "b$i" => 'foo'));
        }

        $cursor = $this->object->find()->limit(1);

        $this->assertNotNull($cursor->getNext());
        $this->assertEquals(NULL, $cursor->getNext());

        $cursor->reset();
        $cursor->limit(0);

        for ($i=0; $i<20; $i++) {
            $this->assertNotNull($cursor->getNext());
        }
    }
    
    /* 
     * TODO: add actual functionality testing 
     * BLOCKED BY: buildbot support for master/slave
     */
    public function testSlaveOkay() {
        $this->sharedFixture->foo->drop();
        $c = $this->sharedFixture->foo->createCollection("foo", true);
        $c->findOne();

        $cursor = $c->find()->slaveOkay()->tailable();
        $cursor->getNext();

        $cursor->reset();
        $cursor = $c->find()->slaveOkay(false)->tailable(false);
        $cursor->slaveOkay(true)->tailable(true);
        $cursor->getNext();

        $c->drop();
    }

    public function testSlaveOkay2() {
        $this->assertFalse(MongoCursor::$slaveOkay);
        MongoCursor::$slaveOkay = true;
        $this->assertTrue(MongoCursor::$slaveOkay);
    }

    public function testImmortal() {
      $this->object->find()->immortal()->next();
      $this->object->find()->immortal(true)->next();
      $this->object->find()->immortal(false)->next();
    }

    /*
     * Doesn't actually test functionality
     */
    public function testSnapshot() {
      $this->object->insert(array('foo'=>'bar'));
      $cursor = $this->object->find()->snapshot();
      $this->assertNotNull($cursor->getNext());
    }


    /**
     * @expectedException MongoCursorException
     */
    public function testErr1() {
      $this->object->insert(array("num" => 1, "foo" => 1));
      var_dump($this->object->find()->hint(array("num" => 1))->explain());
    }

    /**
     * @expectedException MongoCursorException
     */
    public function testErr2() {
      $this->object->insert(array("num" => 1, "foo" => 1));
      var_dump($this->object->find()->hint(array("num" => 1))->next());
    }

    /**
     * @expectedException MongoCursorException
     */
    public function testErr3() {
      $this->object->insert(array("num" => 1, "foo" => 1));
      var_dump($this->object->find()->hint(array("num" => 1))->getNext());
    }

    public function testDead() {
      $this->object->insert(array('x' => 1));
      $cursor = $this->object->find();
      $this->assertFalse($cursor->dead());
      $cursor->next();
      $this->assertTrue($cursor->dead());
    }

    public function testExplainLimit() {
      for ($i=0;$i<100;$i++) {
        $this->object->save(array("x" => $i));
      }

      $q = array("x" => array('$gt' => 50));
      $soft = $this->object->find($q)->limit(20)->explain();
      $hard = $this->object->find($q)->limit(-20)->explain();

      $this->assertEquals(20, $soft['n']);
      $this->assertEquals(20, $hard['n']);
    }

    public function testSpecial() {
      $this->object->insert(array("name" => "joe", "age" => 14));
      $this->object->insert(array("name" => "karen", "age" => 20));
      $this->object->insert(array("name" => "bill", "age" => 30));

      $query = array('age' => array('$lt' => 15));
      $names = $this->object->db->command(array('distinct' => 'c', 'key' => 'name', 'query' => $query));

      $this->assertEquals(1, count($names['values']), json_encode($names));
      $this->assertEquals("joe", $names['values'][0], json_encode($names));
    }

    public function testTimeout2() {
      $cmd = $this->object->db->selectCollection('$cmd');

      for ($i=0; $i<10000; $i++) {
        $this->object->insert(array("name" => "joe".$i, "interests" => array(rand(), rand(), rand())));
      }

      // shouldn't time out
      $r = $this->object->find()->timeout(5000)->getNext();

      // not testing functionality, just making sure it's testing the right data
      $this->assertEquals("joe", substr($r['name'], 0, 3));

      // shouldn't time out, does take a while
      $query = 'r = 0; cursor = db.c.find(); while (cursor.hasNext()) { x = cursor.next(); for (i=0; i<200; i++) { if (x.name == "joe"+i) { r++; } } } return r;';
      $r = $cmd->find(array('$eval'  => $query))->limit(-1)->getNext();
    }

    /**
     * @expectedException MongoCursorTimeoutException
     */
    public function testTimeout() {
      $cmd = $this->object->db->selectCollection('$cmd');

      for ($i=0; $i<10000; $i++) {
        $this->object->insert(array("name" => "joe".$i, "interests" => array(rand(), rand(), rand())));
      }

      // should time out
      $query = 'r = 0; cursor = db.c.find(); while (cursor.hasNext()) { x = cursor.next(); for (i=0; i<200; i++) { if (x.name == "joe"+i) { r++; } } } return r;';
      $cursor = $cmd->find(array('$eval'  => $query))->limit(-1)->timeout(2000);
      $this->assertNull($cursor->getNext());
    }

    public function testTimeout3() {
      for ($i=0; $i<10000; $i++) {
        $this->object->insert(array("name" => "joe".$i, "interests" => array(rand(), rand(), rand())));
      }

      $cmd = $this->object->db->selectCollection('$cmd');

      $query = 'r = 0; cursor = db.c.find(); while (cursor.hasNext()) { x = cursor.next(); for (i=0; i<200; i++) { if (x.name == "joe"+i) { r++; } } } return r;';
      $count = 0;
      for ($i=0; $i<3; $i++) {
        $cursor = $cmd->find(array('$eval'  => $query))->limit(-1)->timeout(500);
        
        try {
          $x = $cursor->getNext();
          $this->assertFalse(true, json_encode($x));
        }
        catch(MongoCursorTimeoutException $e) {
          $count++;
        }
      }

      $this->assertEquals(3, $count);
      $x = $this->object->findOne();
      $this->assertNotNull($x);
      $this->assertTrue(array_key_exists('name', $x), json_encode($x));
      $this->assertTrue(array_key_exists('interests', $x), json_encode($x));
    }

    public function testKillConnection() {
      $m = new Mongo();
      $c = $m->phpunit->kill;
      $c->drop();

      for($i=0; $i<10; $i++) {
        $c->insert(array("x" => $i));
      }

      $cursor = $c->find();
      $cursor->next();

      unset($m);
      sleep(1);

      while($cursor->next()) {}

      $this->sharedFixture->phpunit->kill->drop();
    }

    public function testKillCursors() {
      $c = $this->object;

      for ($i=0; $i<100; $i++) {
        $c->insert(array("x" => $i));
      }
      
      $carr = array();
      $carr[] = $c->find(array("x" => array('$gt' => 80)));
      $carr[] = $c->find(array("x" => array('$lt' => 40)));
      $carr[] = $c->find(array("x" => array('$gt' => 40, '$lt' => 80)));

      foreach ($carr as $cursor) {
        $this->assertFalse($cursor->valid());
        $cursor->next();
        $this->assertTrue($cursor->valid());
      }
    }

    public function testFatalForEach() {
        if (preg_match($this->sharedFixture->version_51, phpversion())) {
            $this->markTestSkipped("who knows what 5.1 does with fatal errors? probably something stupid.");
            return;
        }

        $output = "";
        $exit_code = 0;
        exec("php tests/fatal3.php", $output, $exit_code);
        $uncallable = "Fatal error: Call to a member function foo() on a non-object";

        if (count($output) > 0) {
            $this->assertEquals($uncallable, substr($output[1], 0, strlen($uncallable)), json_encode($output)); 
        }
    }

    public function testManualDtor1() {
        $mongo = new Mongo();
        $cursor = $mongo->phpunit->c->find();
        unset($mongo);
        $this->assertNull($cursor->getNext());
    }

    public function testManualDtor2() {
        $mongo = new Mongo();
        $c = $mongo->phpunit->bar;
        $c->insert(array("x"=>1));

        $cursor = $c->find();
        $cursor->next();
        unset($cursor);
        $cursor = $c->find();
        $this->assertNotNull($cursor->getNext());
    }

    public function testAddOption() {
        $this->object->ensureIndex(array("x" => 1));
        for ($i=0; $i<20; $i++) {
            $this->object->insert(array("x" => $i));
        }
        
        $cursor = $this->object->find()->addOption('$min', array("x" => 15));
        $this->assertTrue($cursor instanceof MongoCursor, get_class($cursor));
        
        foreach($cursor as $v) {
            $this->assertGreaterThanOrEqual(15, $v['x']);
        }
    }

    public function testGeoBox() {
      $this->markTestSkipped("server bug");
      return;

      $this->object->ensureIndex(array("loc" => "2d"), array("min" => 0, "max" => 10));

      for($i=1; $i<10; $i++) {
        for ($j=1; $j<10; $j++) {
          $this->object->insert(array("loc" => array($i, $j)));
        }
      }

      $cursor = $this->object->find(array("loc" => array('$within' => array('$box' => array(array(4,4), array(6,6))))))->sort(array("loc" => 1));

      for ($i=4; $i<7; $i++) {
        for ($j=4; $j<7; $j++) {
          $val = $cursor->getNext();
          $this->assertEquals($i, $val['loc'][0]);
          $this->assertEquals($j, $val['loc'][1]);
        }
      }
    }
      
    public function testFields() {
      $this->object->insert(array("x" => 1, "y" => 1));

      $cursor = $this->object->find()->fields(array("x"=>1));
      $x = $cursor->getNext();
      $this->assertTrue(array_key_exists('x', $x));
      $this->assertTrue(array_key_exists('_id', $x));
      $this->assertFalse(array_key_exists('y', $x));

      $cursor = $this->object->find(array(), array("y" => 1))->fields(array("x"=>1));
      $x = $cursor->getNext();
      $this->assertTrue(array_key_exists('x', $x));
      $this->assertTrue(array_key_exists('_id', $x));
      $this->assertFalse(array_key_exists('y', $x));
      
      $cursor = $this->object->find(array(), array("y" => 1))->fields(array("x"=>1))->fields(array("y"=>1));
      $x = $cursor->getNext();
      $this->assertTrue(array_key_exists('y', $x));
      $this->assertTrue(array_key_exists('_id', $x));
      $this->assertFalse(array_key_exists('x', $x));
      
      $fields = array("y" => 1);
      $cursor = $this->object->find(array(), $fields)->fields($fields)->fields($fields);
      $x = $cursor->getNext();
      $this->assertTrue(array_key_exists('y', $x));
      $this->assertTrue(array_key_exists('_id', $x));
      $this->assertFalse(array_key_exists('x', $x));
    }

    /**
     * @expectedException MongoCursorTimeoutException
     */
    public function testStaticTimeout() {
      $this->markTestSkipped("for now");
      return;

      MongoCursor::$timeout = 1;

      for ($i=0; $i<1000; $i++) {
        $this->object->insert(array("x" => "sdfjnaireojaerkgmdfkngkdsflngklsgntoigneorisgmsrklgd$i", "y" => $i));
      }

      $rows = $this->object->find(array('$eval' => 'r = 0; cursor = db.c.find(); while (cursor.hasNext()) { x = cursor.next(); for (i=0; i<200; i++) { if (x.name == "joe"+i) { r++; } } } return r;'));
      foreach ($rows as $row);

      MongoCursor::$timeout = 30000;
    }

    public function testErrorCode() {
        $code = 0;
        $this->object->insert(array("_id" => 1), array("safe" => true));
        try {
            $this->object->insert(array("_id" => 1), array("safe" => true));
        }
        catch(MongoCursorException $e) {
            $code = $e->getCode();
        }

        $this->assertEquals(11000, $code);
    }
}
?>
