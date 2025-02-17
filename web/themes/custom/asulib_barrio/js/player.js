/**
 * Setup the player after the DOM is loaded so we can
 * add the track and button listeners.
 */
Drupal.behaviors.performance = {
  attach: function (context, settings) {
    once('performance', 'html').forEach((element) => {
      /**
       * Add tooltips to the tracks.
       */
      const tooltipTriggerList = document.querySelectorAll('#player_tracks [data-bs-toggle="tooltip"]')
      const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))

      /**
       * Hold the current track element so we can find the
       * previous and next elements for the previous and next buttons.
       */
      let currentTrack = null
      let currentTrackIndex = null;
      let trackList = [];

      /** Next Track Button */
      function nextTrackListener(e) {
        // Do nothing if we have no tracks or nothing has loaded yet.
        if (trackList.length < 1 || currentTrackIndex == null) {
          return;
        }

        // Next if in the list or circle back to the start.
        let next = currentTrackIndex + 1;
        currentTrackIndex = (next in trackList) ? next : 0;

        loadTrack(trackList[currentTrackIndex]);
      }
      const playerBarNextTrack = document.getElementById('player_bar_next_track')
      playerBarNextTrack.addEventListener('click', nextTrackListener)
      playerBarNextTrack.addEventListener('keydown', (e) => {
        if (e.key == 'Enter') { nextTrackListener(e) }
      })

      /** Previous Track Button */
      function previousTrackListener(e) {
        // Do nothing if we have no tracks or nothing has loaded yet.
        if (trackList.length < 1 || currentTrackIndex == null) {
          return;
        }

        // Previous if in the list or circle back to the last.
        let prev = currentTrackIndex - 1;
        currentTrackIndex = (prev in trackList) ? prev : trackList.length - 1;

        loadTrack(trackList[currentTrackIndex]);
      }

      const playerBarPrevTrack = document.getElementById('player_bar_prev_track')
      playerBarPrevTrack.addEventListener('click', previousTrackListener)
      playerBarPrevTrack.addEventListener('keydown', (e) => {
        if (e.key == 'Enter') { previousTrackListener(e) }
      })

      /*
       * Loads a track list track element into the player bar
       * and starts it.
       *
       * We made it separate from the event listener so it can be
       * called by the next and previous buttons.
       */
      function loadTrack(targetTrack) {
        // Set the current track so the prev/next buttons
        // can reference it.
        currentTrack = targetTrack
        currentTrackIndex = trackList.indexOf(currentTrack);

        // Set playing status.
        document.querySelectorAll('.player_track.table-active')?.forEach((playing) => playing.classList.remove('table-active'))
        currentTrack.classList.add('table-active')

        // Grab the player, set it to the current track's source,
        // load, and play it.
        const player = document.getElementById('player_bar_player')
        player.dataset.analyticsPlayed = 'false'
        trackAudio = currentTrack.querySelector('audio');
        if (trackAudio) {
          player.innerHTML = trackAudio.innerHTML
          player.load()
          player.play()
        }
        else {
          // No audio for the track. Move on to the next track.
          document.getElementById('player_bar_player').onended();
        }
      }

      /**
       * Event listener to load a track and display the player bar,
       * if hidden.
       *
       * Includes a catch for anchor tags within the track item
       * (currently used for composer search links).
       */
      function loadTrackEvent(e) {
        // Allow links within the track box to work.
        if (e.target.href) {
          window.location = e.target.href
          return
        }

        // Ensure the player bar is visible.
        const playerBar = document.getElementById('player_bar')
        if (playerBar.style.display = 'none') {
          playerBar.classList.add('fixed-bottom')
          playerBar.style.display = 'flex'
        }

        loadTrack(e.currentTarget);
      }

      // Add the track click event listeners.
      document.querySelectorAll('#player_tracks .player_track').forEach(function (e) {

        if (e.querySelector('audio')) {
          trackList.push(e);
          e.addEventListener('click', loadTrackEvent)
          e.addEventListener('keydown', (e) => {
            if (e.key == 'Enter') { loadTrackEvent(e) }
          })
        }
      })

      // Auto-advance track.
      document.getElementById('player_bar_player').onended = function () {
        if (currentTrack) {
          let next = currentTrackIndex + 1;
          switch (playerRepeatMode) {
            case 'none':
              if (next in trackList){
                loadTrack(trackList[currentTrackIndex]);
              }
              break;
            case 'track':
              loadTrack(currentTrack)
              break;
            case 'all':
              currentTrackIndex = (next in trackList) ? next : 0;
              loadTrack(trackList[currentTrackIndex]);
              break;
          }
        }
      }

      // Repeat Toggle
      const repeatToggle = document.getElementById('player_bar_repeat')
      let playerRepeatMode = 'none'
      function toggleRepeatMode() {
        switch (playerRepeatMode) {
          case 'none':
            playerRepeatMode = 'all'
            break
          case 'all':
            playerRepeatMode = 'track'
            break
          case 'track':
            playerRepeatMode = 'none'
            break
        }
        repeatToggle.getElementsByTagName('span')[0].innerText = playerRepeatMode
      }
      repeatToggle.addEventListener('click', toggleRepeatMode)
      repeatToggle.addEventListener('keydown', (e) => {
        if (e.key == 'Enter') { toggleRepeatMode(e) }
      })

      /** Toggle Buffering Notice. **/
      const player = document.getElementById('player_bar_player')
      const statusBar = document.getElementById('player_bar_status')
      player.addEventListener('waiting', (e) => {
        statusBar.style.display = 'block'
      })
      player.addEventListener('canplaythrough', (e) => {
        statusBar.style.display = 'none'
      })

      /** Track Play Analytics. **/
      player.addEventListener('timeupdate', (e) => {
        const playThreshold = 30
        if (player.dataset.analyticsPlayed == 'true') {
          return
        }
        const playTimes = player.played
        let timePlayed = 0
        for (let i = 0; i < playTimes.length; i++) {
          timePlayed += playTimes.end(i) - playTimes.start(i)
          if (timePlayed >= playThreshold) {
            // We've played it and won't count it again until played set to false.
            player.dataset.analyticsPlayed = true
            fetch(`/asu-item-analytics/track/${currentTrack.dataset.trackId}/played`).then(response => {
              if (!response.ok) {
                console.error(`Could not increment play count for ${currentTrack.dataset.trackId}`)
              }
              return response.json()
            }).then(data => {
              currentTrack.querySelector('.track-plays').textContent = data.play_count.toLocaleString()
            })
            break
          }
        }
      })
    })
  }
}
