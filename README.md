# Light Twitter Widget

Create a simple widget of your most recent tweet on Twitter within Wordpress using a easy-to-use API. To reduce the number of calls to the Twitter API, the plugin comes with caching.

## Getting Started

### Setup Twitter

1. Download the repo, move it to your plugins folder and activate the plugin in the back end of your WordPress instance.
2. Visit the [Twitter Application Management](https://apps.twitter.com/) and create a new application for your personal WordPress instance.
3. Change to the *Keys and Access Tokens* Tab and keep the *Consumer Key (API Key)* and *Consumer Secret (API Secret)* in mind.
4. Scroll to the *Your Access Token* section and click on *Create my Access Token* and keep the *Access Token*, *Access Token Secret* in mind.
5. Open the settings of the *Light Twitter Widget* in the back end of your WordPress instance and enter the keys and tokens in the corresponding input fields and click *Save*.

### Add the widget

1. Open the lucky post or page in the back end of your WordPress instance.
2. Add the following example code to the content area:
```
[jw_lighttwitterwidget_make]
	  <a class="clickable-avatar" data-preset-on="href" data-preset="https://twitter.com/screen_name()">
         <img data-avatar>
      </a>
      <span class="tweet" data-preset="tweet()"></span>
      <span class="date" data-preset="Tweeted years(x year[s])months(x month[s])days(x day[s])hours(x hour[s])minutes(x minute[s])seconds(x second[s]) ago."></span>
[/jw_lighttwitterwidget_make]
```
3. Done! Give it a try in the front end.

## Customize widget

As you might have realized, the html-elements within the shortcode come with special html-attributes in order to propagate the html-elements with your Twitter information.

### HTML-Attributes

#### data-preset

In order to propagate Twitter information within a specific html-element, add the html-attribute *data-preset* to the html-element and set your desired preset text as its value. You can use the following functions within the html-attribute value:
1. *screen_name()* - Your username
2. *name()* - Your full name
3. *tweet()* - Your tweet
4. *years()*, *months()*, *hours()*, *minutes()*, *seconds()* - The lifetime of the tweet in years, months, hours, minutes or seconds. Please note that as soon as one of these functions return 1 or greater, all following time functions will return an empty string.

The plugin will set the content of html-element accordingly.

##### Example
```
You define:
<span data-preset="This is my tweet: tweet()"></span>

The plugin transforms it to:
<span data-preset="This is my tweet: tweet()">This is my tweet: My first tweet!</span>
```

#### data-preset-on

You want to apply the preset to an html-attribute instead? Add the html-attribute *data-preset-on* to your html-element.

##### Example
```
You define:
<a data-preset="https://twitter.com/screen_name()" data-preset-on="href">Follow me</a>

The plugin transforms it to:
<a data-preset="https://twitter.com/screen_name()" data-preset-on="href" href="https://twitter.com/daffunn">Follow me</a>
```

#### data-avatar

This html-attribute will cause the plugin to add the avatar image to the html-element, either as a *src* or as a *background image*.

##### Example
```
You define:
<img data-avatar>

The plugin transforms it to:
<img data-avatar src="https://pbs.twimg.com/profile_images/1281607341/avatar_bigger.jpg">
```

#### data-error
If you add this html-attribute to any of your html-elements and an error occurs, the plugin will add the defined value as the content of html-element. Additionally the *error* class will be added to the root html-element of the widget.

##### Example
```
You define:
[jw_lighttwitterwidget_make]<span data-error="Error!"></span>[/jw_lighttwitterwidget_make]

The plugin transforms it to:
<div class="jw_lighttwitterwidget_widget error">
     <span data-error="Error!">Error!</span>
</div>
```

#### data-no-tweets

If you add this html-attribute to any of your html-elements and you have no tweets on Twitter, the plugin will add the defined value as a content to the html-element. Additionally the *no-tweets* class will be added to the root html-element of the widget.

##### Example
```
You define:
[jw_lighttwitterwidget_make]<span data-no-tweets="No tweets!"></span>[/jw_lighttwitterwidget_make]

The plugin transforms it to:
<div class="jw_lighttwitterwidget_widget no-tweets">
     <span data-no-tweets="No tweets!">No tweets!</span>
</div>
```
