(function () {
  "use strict";

  var POLL_INTERVAL_MS = 3000;

  var threadMessages = document.getElementById("threadMessages");
  var composerForm = document.getElementById("composerForm");
  var statusDot = document.getElementById("statusDot");
  var threadStatus = document.getElementById("threadStatus");

  function scrollToBottom() {
    if (threadMessages) {
      threadMessages.scrollTop = threadMessages.scrollHeight;
    }
  }

  scrollToBottom();

  var composerInput = document.querySelector(
    ".thread-composer input[name='content']",
  );
  if (composerInput) {
    composerInput.focus();
  }

  if (!threadMessages || !composerForm) {
    return; // inbox page — nothing else to wire up
  }

  var withId = threadMessages.getAttribute("data-with-id");
  var lastId = parseInt(threadMessages.getAttribute("data-last-id") || "0", 10);
  var myUserId = parseInt(threadMessages.getAttribute("data-my-id") || "0", 10);
  var csrfInput = composerForm.querySelector("input[name='csrf_token']");
  var bottomAnchor = document.getElementById("bottom");

  function splitTimestamp(sentAt) {
    var parts = sentAt.split(/[- :]/);
    return new Date(
      parts[0],
      parts[1] - 1,
      parts[2],
      parts[3],
      parts[4],
      parts[5],
    );
  }

  function formatTime(sentAt) {
    var d = splitTimestamp(sentAt);
    var months = [
      "Jan",
      "Feb",
      "Mar",
      "Apr",
      "May",
      "Jun",
      "Jul",
      "Aug",
      "Sep",
      "Oct",
      "Nov",
      "Dec",
    ];
    var dd = String(d.getDate()).padStart(2, "0");
    var hh = String(d.getHours()).padStart(2, "0");
    var mm = String(d.getMinutes()).padStart(2, "0");
    return dd + " " + months[d.getMonth()] + ", " + hh + ":" + mm;
  }

  // ---- Presence (online / last seen) ----
  function formatLastSeen(lastSeenStr) {
    var d = splitTimestamp(lastSeenStr);
    var secondsAgo = Math.floor((Date.now() - d.getTime()) / 1000);

    if (secondsAgo <= 60) return "Online";

    var minutes = Math.floor(secondsAgo / 60);
    if (minutes < 60) {
      return (
        "Last seen " + minutes + (minutes === 1 ? " min ago" : " mins ago")
      );
    }

    var hours = Math.floor(minutes / 60);
    if (hours < 24) {
      return "Last seen " + hours + (hours === 1 ? " hr ago" : " hrs ago");
    }

    return "Last seen " + formatTime(lastSeenStr);
  }

  function updatePresence(otherLastSeen, otherOnline) {
    if (!threadStatus || !otherLastSeen) return;

    threadStatus.textContent = formatLastSeen(otherLastSeen);
    threadStatus.classList.toggle("is-online", !!otherOnline);
    if (statusDot) {
      statusDot.classList.toggle("is-online", !!otherOnline);
    }
  }

  function appendMessage(msg) {
    if (document.querySelector('[data-message-id="' + msg.message_id + '"]')) {
      return;
    }

    var row = document.createElement("div");
    row.className =
      "bubble-row " +
      (parseInt(msg.sender_id, 10) === myUserId ? "is-mine" : "is-theirs");
    row.setAttribute("data-message-id", msg.message_id);

    var bubble = document.createElement("div");
    bubble.className = "bubble";

    String(msg.content)
      .split("\n")
      .forEach(function (line, i) {
        if (i > 0) bubble.appendChild(document.createElement("br"));
        bubble.appendChild(document.createTextNode(line));
      });

    var timeSpan = document.createElement("span");
    timeSpan.className = "bubble-time";
    timeSpan.textContent = formatTime(msg.sent_at);
    bubble.appendChild(timeSpan);

    row.appendChild(bubble);
    bottomAnchor
      ? threadMessages.insertBefore(row, bottomAnchor)
      : threadMessages.appendChild(row);

    var emptyState = threadMessages.querySelector(".thread-empty");
    if (emptyState) emptyState.remove();

    lastId = Math.max(lastId, parseInt(msg.message_id, 10));
    scrollToBottom();
  }

  composerForm.addEventListener("submit", function (e) {
    e.preventDefault();
    var content = composerInput.value.trim();
    if (content === "") return;

    var sendBtn = composerForm.querySelector("button[type='submit']");
    if (sendBtn) sendBtn.disabled = true;

    var body = new URLSearchParams();
    body.append("with", withId);
    body.append("content", content);
    body.append("csrf_token", csrfInput.value);

    fetch("messages-send.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: body.toString(),
    })
      .then(function (res) {
        return res.json();
      })
      .then(function (data) {
        if (data.success) {
          appendMessage(data.message);
          composerInput.value = "";
        } else {
          alert(data.error || "Couldn't send that message.");
        }
      })
      .catch(function () {
        alert("Network error — please try again.");
      })
      .finally(function () {
        if (sendBtn) sendBtn.disabled = false;
        composerInput.focus();
      });
  });

  function poll() {
    fetch(
      "messages-poll.php?with=" +
        encodeURIComponent(withId) +
        "&since_id=" +
        lastId,
    )
      .then(function (res) {
        return res.json();
      })
      .then(function (data) {
        (data.messages || []).forEach(appendMessage);
        updatePresence(data.other_last_seen, data.other_online);
      })
      .catch(function () {
        /* silent — retries next tick */
      });
  }

  setInterval(poll, POLL_INTERVAL_MS);
})();
