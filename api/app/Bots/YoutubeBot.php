<?php
	
	namespace App\Bots;
	
	use Illuminate\Http\Client\Response;
	use Illuminate\Support\Facades\Http;
	use RTippin\Messenger\MessengerBots;
	use RTippin\Messenger\Support\BotActionHandler;
	use Throwable;
	
	class YoutubeBot extends BotActionHandler
	{
		/**
		 * Endpoint we gather data from.
		 */
		const API_ENDPOINT = 'https://www.googleapis.com/youtube/v3/search';
		
		/**
		 * The bots settings.
		 *
		 * @return array
		 */
		public static function getSettings() : array
		{
			return [
				'alias' => 'youtube',
				'description' => 'Get the top video results for a youtube search. [ !youtube {search} ]',
				'name' => 'Youtube Videos Search',
				'unique' => true,
				'triggers' => ['!youtube', '!yt'],
				'match' => MessengerBots::MATCH_STARTS_WITH_CASELESS,
			];
		}
		
		/**
		 * @return array
		 */
		public function rules() : array
		{
			return [
				'limit' => ['nullable', 'integer', 'min:1', 'max:10'],
			];
		}
		
		/**
		 * @throws Throwable
		 */
		public function handle() : void
		{
			$search = $this->getParsedMessage();
			
			if( ! is_null($search)) {
				$youtube = $this->getYoutubeSearch($search);
				if($youtube->successful()) {
					$this->sendYoutubeResultMessages($search, $youtube->json('items'));
					
					return;
				}
			}
			
			$this->sendInvalidSearchMessage();
			
			$this->releaseCooldown();
		}
		
		/**
		 * @param string $search
		 * @param array $results
		 *
		 * @throws Throwable
		 */
		private function sendYoutubeResultMessages(string $search, array $results) : void
		{
			$this->composer()->emitTyping()->message("I found the following video(s) for ( $search ) :");
			
			foreach($results as $result) {
				dump($result);
				$this->composer()->message($result[ 'id' ][ 'videoId' ], extra: ['type' => 'youtube']);
				$urlString = "https://www.googleapis.com/youtube/v3/videos?key=\(apiKey)&part=contentDetails&id=";

//				$this->composer()->message("https://youtu.be/{$result['id']['videoId']}");
			}
			
			
		}
		
		/**
		 * @throws Throwable
		 */
		private function sendInvalidSearchMessage() : void
		{
			$this->composer()->emitTyping()->message('Please select a valid search term, i.e. ( !youtube Stairway To Heaven )');
		}
		
		/**
		 * @param string $search
		 * @return Response
		 */
		private function getYoutubeSearch(string $search) : Response
		{
			return Http::acceptJson()->timeout(5)->get(self::API_ENDPOINT, [
				'key' => config('messenger-bots.youtube_api_key'),
				'maxResults' => ($this->getPayload('limit') ?? 1),
				'q' => $search,
				'part' => 'id',
				'type' => 'video',
			]);
		}
		
		/**
		 * @param string $search
		 * @return Response
		 */
		private function getVideoDetails(string $id) : Response
		{
//			return Http::acceptJson()->timeout(5)->get("https://www.googleapis.com/youtube/v3/videos", [
//				'key' => config('messenger-bots.youtube_api_key'),
//				'maxResults' => ($this->getPayload('limit') ?? 1),
//				'q' => $search,
//				'part' => 'id',
//				'type' => 'video',
//			]);
		}
	}
