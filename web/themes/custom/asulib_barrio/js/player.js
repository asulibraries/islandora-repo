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

      /** Next Track Button */
      function nextTrackListener (e) {
        if (currentTrack) {
          const next = currentTrack.nextElementSibling ?? currentTrack.parentElement.firstElementChild
          loadTrack(next)
        }
      }
      const playerBarNextTrack = document.getElementById('player_bar_next_track')
      playerBarNextTrack.addEventListener('click', nextTrackListener)
      playerBarNextTrack.addEventListener('keydown', (e) => {
        if (e.key == 'Enter') { nextTrackListener(e) }
      })

      /** Previous Track Button */
      function previousTrackListener (e) {
        if (currentTrack) {
          const previous = currentTrack.previousElementSibling ?? currentTrack.parentElement.lastElementChild
          loadTrack(previous)
        }
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
      function loadTrack (targetTrack) {
        // Set the current track so the prev/next buttons
        // can reference it.
        currentTrack = targetTrack

        // Set playing status.
        document.querySelectorAll('#player_tracks > .playing')?.forEach((playing) => playing.classList.remove('playing'))
        currentTrack.classList.add('playing')

        // Grab the player, set it to the current track's source,
        // load, and play it.
        const player = document.getElementById('player_bar_player')
        player.dataset.analyticsPlayed = 'false'
        player.innerHTML = currentTrack.querySelector('audio')?.innerHTML
        player.load()
        player.play()
      }

      /**
       * Event listener to load a track and display the player bar,
       * if hidden.
       *
       * Includes a catch for anchor tags within the track item
       * (currently used for composer search links).
       */
      function loadTrackEvent (e) {
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

        loadTrack(e.currentTarget)
      }

      // Add the track click event listeners.
      document.querySelectorAll('#player_tracks > li').forEach(function (e) {
        e.addEventListener('click', loadTrackEvent)
        e.addEventListener('keydown', (e) => {
          if (e.key == 'Enter') { loadTrackEvent(e) }
        })
      })

      // Auto-advance track.
      document.getElementById('player_bar_player').onended = function () {
        if (currentTrack) {
          switch (playerRepeatMode) {
            case 'none':
              const next = currentTrack.nextElementSibling
              if (next) {
                loadTrack(next)
              }
              break
            case 'all':
              loadTrack(currentTrack.nextElementSibling ?? currentTrack.parentElement.firstElementChild)
              break
            case 'track':
              loadTrack(currentTrack)
              break
          }
        }
      }

      // Repeat Toggle
      const repeatToggle = document.getElementById('player_bar_repeat')
      let playerRepeatMode = 'none'
      function toggleRepeatMode () {
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
              currentTrack.querySelector('span.track-plays').textContent = data.play_count.toLocaleString()
            })
            break
          }
        }
      })
    })
  }
}
