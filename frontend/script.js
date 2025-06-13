function updateNotificationCount() {
    $.ajax({
        url: "../backend/fetch_notifications.php",
        method: "GET",
        success: function(response) {
            let notifications = JSON.parse(response);
            let unreadCount = notifications.filter(n => n.status === 'unread').length;
            $("#notification-count").text(unreadCount);
        }
    });
}
setInterval(updateNotificationCount, 5000);