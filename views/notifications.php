<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Notifications</h1>
            <p class="text-sm text-slate-500 mt-1">All your system notifications</p>
        </div>
        <button id="notifPageMarkAllRead" class="text-sm bg-teal text-white px-4 py-2 rounded-lg hover:bg-teal-hover transition-colors">
            Mark all as read
        </button>
    </div>

    <div id="notifPageContainer" class="space-y-2">
        <div class="text-center py-12 text-slate-400">
            <i class="bi bi-arrow-repeat text-3xl animate-spin inline-block"></i>
            <p class="mt-2 text-sm">Loading notifications...</p>
        </div>
    </div>

    <div id="notifPagePagination" class="flex items-center justify-center gap-4 mt-6 hidden">
        <button id="notifPagePrev" class="px-4 py-2 text-sm border rounded-lg hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed" disabled>
            <i class="bi bi-chevron-left"></i> Previous
        </button>
        <span id="notifPageInfo" class="text-sm text-slate-500"></span>
        <button id="notifPageNext" class="px-4 py-2 text-sm border rounded-lg hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed" disabled>
            Next <i class="bi bi-chevron-right"></i>
        </button>
    </div>
</div>
