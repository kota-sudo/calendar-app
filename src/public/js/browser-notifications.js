console.log("browser-notifications.js loaded");

let notificationIntervalId = null;

function setNotificationButtonState(enabled) {
    const btn = document.getElementById("notificationToggle");
    if (!btn) return;

    const label = btn.querySelector(".switch-label");

    btn.classList.remove("on", "off");
    btn.classList.add(enabled ? "on" : "off");
    btn.setAttribute("aria-pressed", enabled ? "true" : "false");

    if (label) {
        label.textContent = enabled ? "ON" : "OFF";
    }
}

async function toggleNotifications() {
    if (!("Notification" in window)) {
        alert("このブラウザは通知に対応していません。");
        return;
    }

    const enabled = localStorage.getItem("calendar_notifications_enabled") === "1";

    if (!enabled && Notification.permission !== "granted") {
        const permission = await Notification.requestPermission();

        if (permission !== "granted") {
            setNotificationButtonState(false);
            return;
        }
    }

    if (enabled) {
        localStorage.setItem("calendar_notifications_enabled", "0");
        setNotificationButtonState(false);

        if (notificationIntervalId) {
            clearInterval(notificationIntervalId);
            notificationIntervalId = null;
        }
    } else {
        localStorage.setItem("calendar_notifications_enabled", "1");
        setNotificationButtonState(true);
        startNotificationPolling();
    }
}

async function showCalendarNotification(item) {
    if (!("Notification" in window)) return;
    if (Notification.permission !== "granted") return;

    try {
        if ("serviceWorker" in navigator) {
            const registration = await navigator.serviceWorker.ready;

            await registration.showNotification(item.title, {
                body: item.body,
                icon: "/icons/icon-192.png",
                badge: "/icons/icon-192.png",
                data: {
                    url: item.url || "/calendar"
                }
            });

            return;
        }
    } catch (e) {
        console.error("service worker notification error", e);
    }

    new Notification(item.title, {
        body: item.body,
    });
}

async function checkCalendarNotifications() {
    console.log("checkCalendarNotifications called");

    if (!("Notification" in window)) return;
    if (Notification.permission !== "granted") return;

    try {
        const response = await fetch("/notifications/check", {
            headers: {
                "X-Requested-With": "XMLHttpRequest",
            },
        });

        console.log("response status", response.status);

        if (!response.ok) {
            throw new Error("通知APIの取得に失敗しました。");
        }

        const data = await response.json();

        console.log("notification data", data);

        for (const item of (data.notifications || [])) {
            console.log("show notification", item);
            await showCalendarNotification(item);
        }
    } catch (error) {
        console.error("通知チェックエラー:", error);
    }
}

function startNotificationPolling() {
    if (notificationIntervalId) return;

    console.log("startNotificationPolling");

    checkCalendarNotifications();

    notificationIntervalId = setInterval(() => {
        checkCalendarNotifications();
    }, 60000);
}

document.addEventListener("DOMContentLoaded", () => {
    console.log("DOMContentLoaded fired");

    const btn = document.getElementById("notificationToggle");
    if (!btn) return;

    btn.addEventListener("click", toggleNotifications);

    const enabled = localStorage.getItem("calendar_notifications_enabled") === "1";
    setNotificationButtonState(enabled);

    if (
        enabled &&
        "Notification" in window &&
        Notification.permission === "granted"
    ) {
        startNotificationPolling();
    }
});