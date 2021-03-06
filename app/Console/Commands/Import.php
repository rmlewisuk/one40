<?php

namespace App\Console\Commands;

use App\Archive\LogRepository;
use App\Tweets\Formatter;
use App\Tweets\TweetRepository;
use Illuminate\Console\Command;

class Import extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'one40:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import your Twitter archive';
	/**
	 * @var LogRepository
	 */
	private $logRepo;
	/**
	 * @var Formatter
	 */
	private $formatter;
	/**
	 * @var TweetRepository
	 */
	private $tweets;

	public function __construct(LogRepository $logRepo, Formatter $formatter, TweetRepository $tweets)
    {
        parent::__construct();
	    $this->logRepo = $logRepo;
	    $this->formatter = $formatter;
	    $this->tweets = $tweets;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
	    $this->info('Running importer...');

	    $archiveLog = $this->logRepo->all()->pluck('filename')->toArray();

	    $files = glob(base_path() . '/resources/archive/[0-9][0-9][0-9][0-9]_[0-1][0-9].js');

	    if (! count($files))
	    {
	    	$this->info('No archive files found. Aborting...');
	    	return;
	    }

	    $tweets = [];

	    foreach($files as $filename ) {

            if (in_array(basename($filename), $archiveLog)) {
			    $this->info(basename($filename) . ' already imported, skipping...');
			    continue;
		    }

		    $this->info('Found archive file ' . basename($filename));

		    $fileLines = file($filename);
		    array_shift($fileLines); // remove first line
		    $data = json_decode(implode( '', $fileLines));

		    if (! is_array($data)) {
		    	$this->info('Error: Could not parse JSON for ' . basename($filename) . '. Aborting...');
		    	continue;
		    }

		    $this->warn(count($data) . ' tweets found');

		    if (! empty($data)) {
			    foreach($data as $i => $tweet) {
				    // Create tweet element and add to list
				    $tweets[] = $this->formatter->transformTweet($this->normalizeTweet($tweet));
			    }
			    // Ascending sort, oldest first
			    $tweets = array_reverse($tweets);

			    $this->tweets->addTweets($tweets);
		    }

		    $tweets = [];
		    $this->logRepo->markImported(basename($filename));
	    }
    }

    private function normalizeTweet($tweet) {
	    foreach ($tweet as $k => $v) {
		    // replace empty objects with null
		    if (is_object($v) && count( get_object_vars($v)) === 0) {
			    $tweet->$k = null;
		    }
	    }
	    foreach(['geo', 'coordinates', 'place', 'contributors'] as $property ) {
		    if(! property_exists( $tweet, $property ) ) {
			    $tweet->$property = null;
		    }
	    }
	    return $tweet;
    }
}
