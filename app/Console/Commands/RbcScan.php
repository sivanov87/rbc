<?php namespace App\Console\Commands;

use Illuminate\Console\Command;

use \App\Models\Article;

use \Carbon\Carbon;

class RbcScan extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'rbc_scan';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'rbc_scan';

	// Load page with headers from real person
	private static function loadUrlWithHeaders($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $headers = array(
            'cache-control: max-age=0',
            'upgrade-insecure-requests: 1',
            'user-agent: Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3904.97 Safari/537.36',
            'sec-fetch-user: ?1',
            'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3',
            'x-compress: null',
            'sec-fetch-site: none',
            'sec-fetch-mode: navigate',
            'accept-encoding: deflate, br',
            'accept-language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
        );

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $html = curl_exec($ch);
        curl_close($ch);
        return $html;
    }

	public function handle()
	{
        $html = Self::loadUrlWithHeaders("https://www.rbc.ru/");
        $doc = new \DOMDocument();
        @$doc->loadHTML($html);

        $xpath = new \DOMXpath($doc);
        $elements = $xpath->query("//div[@class='js-news-feed-list']/a");

        foreach( $elements as $element )
        {
            if( ! isset($element->attributes["href"]->value) )
            {
                // TODO
                continue;
            }

            $articleUrl = $element->attributes["href"]->value;
            // Exclude traffic articles
            if( preg_match("#^https?://traffic\.#",$articleUrl))
            {
                //TODO
                continue;
            }
            // Exclude PLUS articles
            if( preg_match("#^https?://plus\.#",$articleUrl))
            {
                //TODO
                continue;
            }
            // Exclude PRO articles
            if( preg_match("#^https?://pro\.#",$articleUrl))
            {
                //TODO
                continue;
            }
            // Exclude STYLE articles
            if( preg_match("#^https?://style\.#",$articleUrl))
            {
                //TODO
                continue;
            }

            $articleUrl = preg_replace("/\?.*$/","",$articleUrl); // clear url params

            $article = Article::firstOrCreate(["url"=>$articleUrl]);
            $article->site_id = 1;
            var_dump($articleUrl);

            // Process article
            $articleDocument = new \DOMDocument();
            $articleHtml = Self::loadUrlWithHeaders($article->url);

            @$articleDocument->loadHTML($articleHtml);
            $articleXpath = new \DOMXpath($articleDocument);
            $content = $articleXpath->query("//div[contains(@class,'article__content')]")[0];
            $titleByH1 = $articleXpath->evaluate("//h1/text()",$content)[0];
            if( $titleByH1 ) {
                $article->title = trim($titleByH1->nodeValue);
            }
            else{
                $titleByDiv = $articleXpath->evaluate("//div[@class='article__header__title']",$content)[0];
                $article->title = trim($titleByDiv->nodeValue);
            }
            $descriptionNode = $articleXpath->query("//div[@class='article__text__overview']",$content)[0];
            if( $descriptionNode ) {
                $article->description = trim($descriptionNode->textContent);
            }
            else
            {
                $descriptionNodeByAnons = $articleXpath->query("//div[@class='article__header__anons']",$content)[0];
                if( $descriptionNodeByAnons)
                    $article->description = trim($descriptionNodeByAnons->textContent);
            }

            $categoryNode = $articleXpath->query("//a[@class='article__header__category']")[0];
            if( $categoryNode )
                $article->category = $categoryNode->textContent;

            $imageDivNode = $articleXpath->query("//div[@class='article__main-image']")[0];
            if( $imageDivNode ) {
                $imageNode = $articleXpath->query("//img[contains(@class,'article__main-image__image')]")[0];
                if ($imageNode) {
                    $article->image_url = $imageNode->attributes["src"]->value;
                }
            }
            else
            {
                $imageNode = $articleXpath->query("//div[contains(@class,'article__main-image__image')]/img")[0];
                if( $imageNode ) {
                    $article->image_url = $imageNode->attributes["src"]->value;
                }
            }
            $textNode = $articleXpath->evaluate("//div[@itemprop='articleBody']")[0];

            if( $descriptionNode )
                $textNode->removeChild( $descriptionNode );
            if( $imageDivNode )
                $textNode->removeChild( $imageDivNode );

            // Clear from advs
            $followNode = $articleXpath->evaluate("//div[@itemprop='articleBody']/div[contains(@class,'news-bar_article')]")[0];
            if( $followNode )
                $textNode->removeChild($followNode);

            $centerNode = $articleXpath->evaluate("//div[@itemprop='articleBody']/div[contains(@class,'l-base__flex__base')]")[0];
            $rightNode = $articleXpath->evaluate("//div[@itemprop='articleBody']/div/div[contains(@class,'l-base__col__right')]")[0];
            if( $rightNode && $centerNode)
                $centerNode->removeChild($rightNode);

            $article->text = trim($textNode->textContent);

            $publishedAtNode = $articleXpath->evaluate("//span[@itemprop='datePublished']")[0];
            if( $publishedAtNode ) {
                $publishedAt = Carbon::parse($publishedAtNode->attributes[2]->value);
                $article->published_at = $publishedAt;
            }

            $article->save();
        }
    }
}
