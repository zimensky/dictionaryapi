<?php
/**
 * Merriam-Webster's API
 */
class Dictionaryapi {
  // API URL
  private $url = 'http://www.dictionaryapi.com/api/v1/references/';
  // Response format
  private $format = 'xml';
  // Access key for dictionaries
  private $dictionaryKey;
  // Access key for Thesaurus
  private $thesaurusKey;
  // Name of current dictionary
  private $dictionary = 'collegiate';
  // List of allowed dictionaries and keys associated with them
  private $dictionaries = array(
    'collegiate' => 'dictionaryKey', // Collegiate Dictionary with Audio
    'thesaurus' => 'thesaurusKey', // Collegiate Thesaurus
    'spanish' => 'dictionaryKey', // Spanish-English Dictionary with Audio
    'medical' => 'dictionaryKey', // Medical Dictionary with Audio
    'learners' => 'dictionaryKey', // Learner's Dictionary with Audio
    'sd2' => 'dictionaryKey', // Elementary Dictionary with Audio (Grades 3-5)
    'sd3' => 'dictionaryKey', // Intermediate Dictionary with Audio (Grades 6-8)
    'sd4' => 'dictionaryKey', // School Dictionary with Audio (Grades 9-11)
  );
  // Array for messages
  private $log = array();

  public function __construct($dictionaryKey, $thesaurusKey) {
    $this->dictionaryKey = $dictionaryKey;
    $this->thesaurusKey = $thesaurusKey;
  }

  /**
   * Returns a log messages
   * @return array
   */
  public function getLog() {
    return $this->log;
  }

  public function clearLog() {
    $this->log = array();
  }

  /**
   * @param $dictionary string Dictionary's "machine name"
   */
  public function setDictionary($dictionary) {
    if (array_key_exists($dictionary, $this->dictionaries)) {
      $this->dictionary = $dictionary;
    } else {
      $this->log[] = 'Trying to set incorrect dictionary.';
    }
  }

  /**
   * Returns a key associated with current dictionary
   */
  public function getKey() {
    return $this->{$this->dictionaries[$this->dictionary]};
  }

  /**
   * Search the $word in current dictionary
   * @param $word string Word to search
   * @return SimpleXMLElement
   */
  public function search($word) {
    $url = $this->url . $this->dictionary . '/' . $this->format . '/' . $word . '?key=' . $this->getKey();
    $xml = simplexml_load_file($url);
    return $xml;
  }

  /**
   * Fetches xml object to associated array
   * @param $xml SimpleXML
   * @return array Associated array for dictionary entry
   *
   */
  public function fetchAssoc($xml) {
    switch ($this->dictionary) {
      case 'collegiate':
        $item = $this->fetchAssocCollegiate($xml);
        break;
      case 'thesaurus':
        $item = $this->fetchAssocThesaurus($xml);
        break;
    }
    return $item;
  }

  private function fetchAssocCollegiate($xml) {
    if (empty($xml->entry)) {
      $this->log[] = 'No entry found in fetchAssoc.';
      return false;
    }

    $item = array();

    // Grouping definitions in one entry
    foreach ($xml->entry as $entry) {
      $def = array();
      foreach ($entry->def->dt as $dt) {
        $def[] = $dt->asXML();
      }
      $item[] = array(
        'headword' => empty($entry->ew) ? '' : (string)$entry->ew,
        'part_of_speech' => empty($entry->fl) ? '' : (string)$entry->fl,
        'sound' => empty($entry->sound->wav) ? '' : (string)$entry->sound->wav,
        'pron' => empty($entry->pr) ? '' : (string)$entry->pr,
        'definitions' => $def,
        'first_use' => empty($entry->def->date) ? '' : (string)$entry->def->date,
      );
    }

    return $item;
  }

  private function fetchAssocThesaurus($xml) {
    if (empty($xml->entry)) {
      $this->log[] = 'No entry found in fetchAssoc.';
      return false;
    }

    $item = array();

    // Because of separated synonyms, antonyms, related words for each entry
    // here is no grouping for definitions as in Collegiate
    foreach ($xml->entry as $entry) {
      foreach ($entry->sens as $sens) {
        $item[] = array(
          'headword' => empty($entry->term->hw) ? '' : (string)$entry->term->hw,
          'part_of_speech' => empty($entry->fl) ? '' : (string)$entry->fl,
          'definition' => empty($sens->mc) ? '' : (string)$sens->mc,
          'example' => empty($sens->vi) ? '' : strip_tags($sens->vi->asXML()),
          'synonyms' => empty($sens->syn) ? '' : $this->parseListToStr((string)$sens->syn),
          'antonyms' => empty($sens->ant) ? '' : $this->parseListToStr((string)$sens->ant),
          'near_antonyms' => empty($sens->near) ? '' : $this->parseListToStr((string)$sens->near),
          'related' => empty($sens->rel) ? '' : $this->parseListToStr((string)$sens->rel),
        );
      }
    }

    return $item;
  }

  /**
   * Parses words lists such as synonyms, antonyms, etc.
   * @param $list string List of words divided by any character
   * @param string $connector
   * @return string
   */
  private function parseListToStr($list, $connector = ',') {
    // Search "word" or "word (word)"
    preg_match_all('/((\w+)(\s\(\w+\))?)/', $list, $result);
    if (empty($result[1]))
      return false;
    else
      return implode($connector, $result[1]);
  }

  /**
   * Parses words lists such as synonyms, antonyms, etc.
   * @param $list string List of words divided by any character
   * @return array
   */
  private function parseListToArray($list) {
    // Search "word" or "word (word)"
    preg_match_all('/((\w+)(\s\(\w+\))?)/', $list, $result);
    if (empty($result[1]))
      return false;
    else
      return $result[1];
  }
}