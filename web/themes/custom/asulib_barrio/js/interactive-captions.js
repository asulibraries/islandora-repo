/*jslint browser: true*/
/*global Audio, Drupal*/
/**
 * @file
 * Displays interactive WebVTT captions window.
 */
(function(Drupal, once) {
  Drupal.behaviors.interactive_captions = {
    attach: function(context, settings) {

      once('interactive_captions', 'audio, video', context).forEach(function(element) {

        function parseTimestamp(timestamp) {
          const [hours, minutes, seconds] = timestamp.split(':').map(Number);
          return hours * 3600 + minutes * 60 + seconds;
        }

        function parseWebVTT(content) {
          timeMarkerRegex = new RegExp("^\\d{2}:\\d{2}:\\d{2}\\.\\d{3} --> \\d{2}:\\d{2}:\\d{2}\\.\\d{3}$");
          const lines = content.split('\n');
          const cues = [];
          let currentCue = null;

          for (const line of lines) {
            const trimmedLine = line.trim();
            if (trimmedLine === 'WEBVTT') {
              continue;
            }
            if (!trimmedLine) {
              // Empty line indicates the end of a cue.
              if (currentCue) {
                cues.push(currentCue);
                currentCue = null;
              }
              continue;
            }

            if (timeMarkerRegex.test(trimmedLine)) {
              // Timing line
              currentCue = {
                start: '',
                end: '',
                text: ''
              };
              const [start, end] = trimmedLine.split(' --> ');
              currentCue.start = parseTimestamp(start);
              currentCue.end = parseTimestamp(end);
            } else if (currentCue) {
              // Text line
              currentCue.text += (currentCue.text ? '\n' : '') + trimmedLine;
            }
          }
          if (typeof currentCue !== 'undefined') {
            cues.push(currentCue);
          }
          return cues;
        }
        if (element.textTracks.length < 1) {
          // Nothing to show.
          return;
        }

        // Using the code for picking a transcript from the islandora code wasn't working. So, I'm just grabbing the first one available.
        bindVttSource(element.querySelector("track[kind='captions'], track[kind='subtitles']").getAttribute('src'));

        //Click listener to the cue divs in the window to change the audio/video play position.
        const updateAVTime = function(e) {
          // Also allow clicking on child elements of the cue element. I.e. the time span element.
          let newTime = e.target.dataset.start ?? e.target.parentElement.dataset.start;
          if (newTime) {
            element.currentTime = newTime;
            element.play();
          }
          else {
            console.warn("Could not update a/v time. No 'start' attribute found.", e.target);
          }
        }

        function bindVttSource(vtt_source) {
          fetch(vtt_source).then(response => {
            if (!response.ok) {
              throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text();
          }).then(text => {
            const cues = parseWebVTT(text);

            // Populate the cue viewer.
            let cueWindow = document.getElementById('interactive-captions-window');
            cues.forEach((cue, index) => {
              let cueDiv = document.createElement('div');
              cueDiv.setAttribute('tabindex', 0);
              cueDiv.setAttribute('data-start', cue.start);
              cueDiv.setAttribute('data-end', cue.end);
              cueDiv.addEventListener('click', updateAVTime);
              cueDiv.addEventListener('keydown', (e) => {
                if (e.key == 'Enter') {
                  updateAVTime(e)
                }
              })
              let formattedTime = `${String(Math.floor(cue.start/3600)).padStart(2, '0')}:${String(Math.floor((cue.start % 3600) / 60)).padStart(2,'0')}:${String(Math.trunc(cue.start % 60)).padStart(2, '0')}` 
              if (cue?.text) {
                cueDiv.innerHTML = `<span class="formatted-time">${formattedTime}</span> ${cue.text.replace(/\n/g, " ")}`;
              } else {
                cueDiv.innerHTML = '';
              }
              cueWindow.appendChild(cueDiv);
            });
            if (cues.length !== 0) {
              cueWindow.style.display = 'block';
            }

            //Time Update listener highlight the cue's div in the window and move it to the center (see player with track changes).
            element.addEventListener('timeupdate', (e) => {
              // Check if a track is showing.
              var showing = e.target.getAttribute('data-showing');
              // Update the caption box.
              let captionsBox = e.target.parentElement.querySelector('div#interactive-captions-window');
              const currentTime = e.target.currentTime;
              let cue = captionsBox.childNodes.forEach((cue, idx, cues) => {
                if (currentTime >= cue.dataset.start && currentTime <= cue.dataset.end) {
                  // Only set active and get focus on first encounter.
                  if (!cue.classList.contains('active')) {
                    cue.classList.add('active');
                  }
                } else {
                  cue.classList.remove('active');
                }
              });
            });
          });
        }
        /** Cue Visibility */
        function isOverflowing(cue) {
          const cueRect = cue.getBoundingClientRect();
          const scrollWindow = document.getElementById('interactive-captions-window')

          if (!scrollWindow) {
            return false;
          }

          const scrollWindowRect = scrollWindow.getBoundingClientRect();

          return cueRect.top < scrollWindowRect.top ||
            cueRect.left < scrollWindowRect.left ||
            cueRect.right > scrollWindowRect.right ||
            cueRect.bottom > scrollWindowRect.bottom;
        }
      })
    }
  }
})(Drupal, once);