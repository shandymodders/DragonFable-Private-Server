(function () {
    "use strict";

    function setupScrollableChat() {
        const chat = document.querySelector("#chat-container [data-id='chat-content']");
        if (!chat) {
            return;
        }

        let followNewest = true;
        let programmaticScroll = false;

        function distanceFromBottom() {
            return chat.scrollHeight - chat.scrollTop - chat.clientHeight;
        }

        function scrollToNewest(instant) {
            programmaticScroll = true;

            if (instant) {
                const previousBehavior = chat.style.scrollBehavior;
                chat.style.scrollBehavior = "auto";
                chat.scrollTop = chat.scrollHeight;
                chat.style.scrollBehavior = previousBehavior;
            } else {
                chat.scrollTo({
                    top: chat.scrollHeight,
                    behavior: "smooth"
                });
            }

            window.setTimeout(function () {
                programmaticScroll = false;
            }, 100);
        }

        chat.addEventListener("scroll", function () {
            if (programmaticScroll) {
                return;
            }

            // A small tolerance prevents tiny rounding differences from disabling follow mode.
            followNewest = distanceFromBottom() <= 45;
        }, { passive: true });

        // Mouse wheel should remain inside the chat panel when there is history to scroll.
        chat.addEventListener("wheel", function (event) {
            const canScroll = chat.scrollHeight > chat.clientHeight;
            if (!canScroll) {
                return;
            }

            const atTop = chat.scrollTop <= 0;
            const atBottom = distanceFromBottom() <= 1;

            if ((event.deltaY < 0 && !atTop) || (event.deltaY > 0 && !atBottom)) {
                event.stopPropagation();
            }
        }, { passive: true });

        const observer = new MutationObserver(function () {
            if (!followNewest) {
                return;
            }

            window.requestAnimationFrame(function () {
                scrollToNewest(false);
            });
        });

        observer.observe(chat, {
            childList: true,
            subtree: true
        });

        // The chat renderer can populate messages shortly after page load.
        window.setTimeout(function () {
            scrollToNewest(true);
        }, 250);
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", setupScrollableChat);
    } else {
        setupScrollableChat();
    }
})();
