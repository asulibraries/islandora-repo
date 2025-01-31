/**
 * Setup the player after the DOM is loaded so we can
 * add the track and button listeners.
 */
Drupal.behaviors.performance = {
    attach: function (context, settings) {
        once('performance', 'html').forEach((element) => {

            /**
             * Hold the current track element so we can find the
             * previous and next elements for the previous and next buttons.
             */
            let current_track = null;

            /** Next Track Button */
            function nextTrackListener(e) {
                if (current_track) {
                    let next = current_track.nextElementSibling ?? current_track.parentElement.firstElementChild;
                    loadTrack(next);
                }
            }
            let player_bar_next_track = document.getElementById('player_bar_next_track');
            player_bar_next_track.addEventListener('click', nextTrackListener);
            player_bar_next_track.addEventListener('keydown', (e) => {
                if (e.key == 'Enter') { nextTrackListener(e); }
            });

            /** Previous Track Button */
            function previousTrackListener(e) {
                if (current_track) {
                    let previous = current_track.previousElementSibling ?? current_track.parentElement.lastElementChild;
                    loadTrack(previous);
                }
            }
            let player_bar_prev_track = document.getElementById('player_bar_prev_track');
            player_bar_prev_track.addEventListener('click', previousTrackListener);
            player_bar_prev_track.addEventListener('keydown', (e) => {
                if (e.key == 'Enter') { previousTrackListener(e); }
            });

            /*
                * Loads a track list track element into the player bar
                * and starts it.
                *
                * We made it separate from the event listener so it can be
                * called by the next and previous buttons.
                */
            function loadTrack(target_track) {

                // Set the current track so the prev/next buttons
                // can reference it.
                current_track = target_track;

                // Set playing status.
                document.querySelectorAll('#player_tracks > .playing')?.forEach((playing) => playing.classList.remove('playing'));
                current_track.classList.add('playing');

                // Grab the player, set it to the current track's source,
                // load, and play it.
                let player = document.getElementById('player_bar_player');
                player.innerHTML = current_track.querySelector('audio')?.innerHTML;
                player.load();
                player.play();
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
                    window.location = e.target.href;
                    return;
                }

                // Ensure the player bar is visible.
                let player_bar = document.getElementById('player_bar');
                if (player_bar.style.display = 'none') {
                    player_bar.classList.add('fixed-bottom');
                    player_bar.style.display = "flex";
                }

                loadTrack(e.currentTarget);
            }

            // Add the track click event listeners.
            document.querySelectorAll("#player_tracks > li").forEach(function (e) {
                e.addEventListener('click', loadTrackEvent);
                e.addEventListener('keydown', (e) => {
                    if (e.key == 'Enter') { loadTrackEvent(e); }
                });
            });

            // Auto-advance track.
            document.getElementById('player_bar_player').onended = function () {
                if (current_track) {
                    switch (player_repeat_mode) {
                        case 'none':
                            let next = current_track.nextElementSibling;
                            if (next) {
                                loadTrack(next);
                            }
                            break;
                        case 'all':
                            loadTrack(current_track.nextElementSibling ?? current_track.parentElement.firstElementChild);
                            break;
                        case 'track':
                            loadTrack(current_track)
                            break;
                    }
                }
            };

            // Repeat Toggle
            let repeat_toggle = document.getElementById('player_bar_repeat');
            let player_repeat_mode = 'none';
            function toggle_repeat_mode() {
                switch (player_repeat_mode) {
                    case 'none':
                        player_repeat_mode = 'all';
                        break;
                    case 'all':
                        player_repeat_mode = 'track';
                        break;
                    case 'track':
                        player_repeat_mode = 'none';
                        break;
                }
                repeat_toggle.getElementsByTagName('span')[0].innerText = player_repeat_mode;
            }
            repeat_toggle.addEventListener('click', toggle_repeat_mode);
            repeat_toggle.addEventListener('keydown', (e) => {
                if (e.key == 'Enter') { toggle_repeat_mode(e); }
            });

            /** Toggle Buffering Notice. **/
            const player = document.getElementById('player_bar_player');
            const status_bar = document.getElementById('player_bar_status');
            player.addEventListener('waiting', (e) => {
                status_bar.style.display = 'block';
	    });
            player.addEventListener('canplaythrough', (e) => {
                status_bar.style.display = 'none';
	    });
        })
    }
};
