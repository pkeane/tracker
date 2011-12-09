{extends "base.tpl"}

{block name="head-meta"}
<meta name="dcterms.date" content="{$ymd}">
{/block}

{block name="header"}
<a href="{$app_root}"><h1>{$main_title}</h1></a>
<a href="tasks/{$yest}">&lt;</a> {$ts|date_format:'D, M d Y'} <a href="tasks/{$tomm}">&gt;</a>
<a class="menu" href="projects">new project</a> 
{/block}

{block name="main"}

<div id="tasks">
<ul class="hour">
<li data-value="1">1</li>
<li data-value="1">1</li>
<li data-value="1">1</li>
<li data-value="1">1</li>
<li data-value="1">1</li>
<li data-value="1">1</li>
<li data-value="1">1</li>
<li data-value="1">1</li>
</ul>
<div class="clear"></div>
<ul class="half">
<li data-value=".5">&frac12;</li>
<li data-value=".5">&frac12;</li>
<li data-value=".5">&frac12;</li>
<li data-value=".5">&frac12;</li>
<li data-value=".5">&frac12;</li>
<li data-value=".5">&frac12;</li>
<li data-value=".5">&frac12;</li>
<li data-value=".5">&frac12;</li>
</ul>
<div class="clear"></div>
<ul class="quarter">
<li data-value=".25">&frac14;</li>
<li data-value=".25">&frac14;</li>
<li data-value=".25">&frac14;</li>
<li data-value=".25">&frac14;</li>
<li data-value=".25">&frac14;</li>
<li data-value=".25">&frac14;</li>
<li data-value=".25">&frac14;</li>
<li data-value=".25">&frac14;</li>
<li data-value=".25">&frac14;</li>
</ul>
<div class="clear"></div>
</div>

<div>
<ul id="projects">
{foreach item=p from=$projects}
{assign var=pid value=$p->id}
<li data-value="{$counts.$pid}" data-project_id="{$p->id}">
{$p->name}
<span class="value">{$counts.$pid}</span>
<ul>
{if $counts.$pid}
<li>{$counts.$pid}</li>
{/if}
</ul>
</li>
{/foreach}
</ul>
</div>


{/block}
