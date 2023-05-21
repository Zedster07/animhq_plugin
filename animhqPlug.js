document.addEventListener('DOMContentLoaded', function() {
  var addSeasonButton = document.querySelector("#animhqAddSeason");
  var addEpisodeButtons = document.querySelectorAll(".animhqAddEpisode");
  var seasonTabs = document.querySelectorAll(".animhq_Season_tab");
  var episodeTabs = document.querySelectorAll(".animhq_Episode_tab");
var maxSIds = parseInt(document.getElementById("maxSids").value);
var maxEIds = parseInt(document.getElementById("maxEids").value);

  addSeasonButton.addEventListener('click', handleAddSeason);

  addEpisodeButtons.forEach(function(button) {
    button.addEventListener('click', handleAddEpisode);
  });

  seasonTabs.forEach(function(seasonTab) {
    var seasonHeader = seasonTab.querySelector('.animhq_Season_header');
    var seasonBody = seasonTab.querySelector('.animhq_Season_body');
    var dropdownButton = seasonHeader.querySelector('.animhq_DropdownButton');

    dropdownButton.addEventListener('click', function() {
      seasonBody.classList.toggle('animhq_ClosedBody');
      dropdownButton.classList.toggle('animhq_Open');
    });
  });

  episodeTabs.forEach(function(episodeTab) {
    var episodeHeader = episodeTab.querySelector('.animhq_Episode_header');
    var episodeBody = episodeTab.querySelector('.animhq_Episode_body');
    var dropdownButton = episodeHeader.querySelector('.animhq_DropdownButton');

    dropdownButton.addEventListener('click', function() {
      episodeBody.classList.toggle('animhq_ClosedBody');
      dropdownButton.classList.toggle('animhq_Open');
    });
  });

  function handleAddSeason() {
    maxSIds = maxSIds + 1;
    var seasonId = maxSIds;
    
    var newSeasonTab = document.createElement('div');
    newSeasonTab.classList.add('animhq_Season_tab');
    newSeasonTab.setAttribute('id', 'season_' + seasonId);
    newSeasonTab.innerHTML = `
      <div class="animhq_Season_header">
        <span class="dropandInput">
          <div class="animhq_DropdownButton">▼</div>
          <input type="text" name="seasons[${seasonId}][name]" value="Season ${seasonId}" placeholder="Season Name" />
           
        </span>
        <input type="hidden" name="seasons[${seasonId}][id]" value="${seasonId}" />
        <div class="animhq_addButton animhqAddEpisode">+ Episode</div>
      </div>
      
        <div class="animhq_Season_body">
          <div class="animhq_Season_body_fields">
            <Label>Season Order:</Label>
            <input type="text" name="seasons[${seasonId}][order]" value="" placeholder="Season Order" />
            <Label>Season Cover:</Label>
            <input type="file" name="seasons[${seasonId}][cover]" placeholder="Season Cover" />
          </div>
          <h3>Episodes:</h3>
          
      
        </div>
    `;
    document.querySelector('#custom-tab .inside').appendChild(newSeasonTab);
    document.querySelector('#season_'+seasonId+' .animhqAddEpisode').addEventListener('click', handleAddEpisode);
    var seasonHeader = newSeasonTab.querySelector('.animhq_Season_header');
    var seasonBody = newSeasonTab.querySelector('.animhq_Season_body');
    var dropdownButton = seasonHeader.querySelector('.animhq_DropdownButton');

    dropdownButton.addEventListener('click', function() {
      seasonBody.classList.toggle('animhq_ClosedBody');
      dropdownButton.classList.toggle('animhq_Open');
    });
  }

  function handleAddEpisode() {
    var seasonTab = this.closest('.animhq_Season_tab');
    var seasonId = seasonTab.getAttribute('id').replace('season_', '');
    maxEIds = maxEIds + 1;
    var episodeId = maxEIds;
    var newEpisodeTab = document.createElement('div');
    newEpisodeTab.classList.add('animhq_Episode_tab');
    newEpisodeTab.setAttribute('season', seasonId);
    newEpisodeTab.innerHTML = `
      <div class="animhq_Episode_header">
        <span class="dropandInput">
          <div class="animhq_DropdownButton">▼</div>
          <input type="text" name="seasons[${seasonId}][episodes][${episodeId}][name]" value="Episode ${episodeId}" placeholder="Episode Name" />
        </span>
        <input type="hidden" name="seasons[${seasonId}][episodes][${episodeId}][id]" value="${episodeId}" />
      </div>
      <div class="animhq_Episode_body">
        <input type="text" name="seasons[${seasonId}][episodes][${episodeId}][order]" placeholder="Episode Order" />
        <input type="text" name="seasons[${seasonId}][episodes][${episodeId}][quality]" placeholder="Episode Quality" />
        <input type="text" name="seasons[${seasonId}][episodes][${episodeId}][video]" placeholder="Episode Video" />
      </div>
    `;
    seasonTab.querySelector('.animhq_Season_body').appendChild(newEpisodeTab);
    var episodeHeader = newEpisodeTab.querySelector('.animhq_Episode_header');
    var episodeBody = newEpisodeTab.querySelector('.animhq_Episode_body');
    var dropdownButton = episodeHeader.querySelector('.animhq_DropdownButton');

    dropdownButton.addEventListener('click', function() {
      episodeBody.classList.toggle('animhq_ClosedBody');
      dropdownButton.classList.toggle('animhq_Open');
    });
  }
});