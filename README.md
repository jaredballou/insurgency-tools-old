# Insurgency Web Tools
These tools are designed to allow players to interact with game data through a functional if not pretty web interface. They interact with the game data files directly to ensure that the information displayed is always accurate. I am not a web developer, and I learned CSS, SVG, and Javascript while creating these tools, so while they're functional and the only thing out there that can do what they do, I am probably going to do complete rebuilds from the ground up of the stat and map tools at least. Eventually the heavy, static pages that are displayed now should be replaced with d3 or some other more-suited visualization as well.

* [Stats](stats.php): Parses theater files as the engine would and generates tables based upon the data. Fairly in-depth damage modeling allowing selection of range, units of measure, and displaying actual damage that would be applied on a player using a body graph. Cross-references between sections and addons, allows selection of previous versions and a rough comparison tool that just dumps the values that differ between two versions, it's actually pretty functional.
  * [X] Read game files and produce stats page
  * [X] Show damage applied based upon ammo, armor, hit location, and distance.
  * [X] Create graphs for damage on body outline
  * [X] Selectable field at start of each row to narrow results
  * [ ] Create Wiki export tool
  * [ ] Add floating headers.
  * [ ] Create single item view which includes all attachments.
  * [ ] Create loadout selection view.
  * [ ] Figure out weight units and try to show speed/stamina impacts.
  * [ ] Clean up and flesh out version comparison tool.
* [Map Viewer](maps.php): Displays map overviews, control points and caches, game types, spawn points, navmesh data, and custom overlay data.
  * [X] Use preparsed JSON as data source.
  * [X] Create parser tool to refresh JSON data when needed from game files.
  * [X] Show selectable layers for each game mode with spawns, control points, and caches.
  * [ ] Integrate with a SourceMod plugin to dump JSON.
  * [ ] Move to d3 or other Javascript data visualization engine
  * [ ] Add in-browser editor for overlay objects
  * [ ] Allow external or client side overlays
* [CVAR list](cvarlist.php): List of cvars. Nuff said.
  * [ ] Cache parsed table.
  * [ ] Remove unwanted columns by default.
