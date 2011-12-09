{extends "base.tpl"}

{block name="main"}

<div class="projectss">
<h2>Projects</h2>

<form action="projects" method="post">
<label for="name">name</label>
<input type="text" name="name">
<input type="submit" value="create new project">
</form>

<ul>
{foreach item=project from=$projects}
<li>
<a href="projects/{$project->id}">{$project->name}</a>
</li>
{/foreach}
</ul>
</div>

{/block}
