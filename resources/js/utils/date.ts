/**
 * Centralized date/time utilities for consistent timezone handling
 * All date operations should use these functions to avoid timezone bugs
 */

/**
 * Format a date to YYYY-MM-DD in LOCAL timezone
 * Use this when extracting date parts from ISO timestamps
 */
export function toLocalDateString(date: Date | string): string {
    const d = typeof date === 'string' ? new Date(date) : date;
    const year = d.getFullYear();
    const month = (d.getMonth() + 1).toString().padStart(2, '0');
    const day = d.getDate().toString().padStart(2, '0');
    return `${year}-${month}-${day}`;
}

/**
 * Format a time to HH:mm in LOCAL timezone
 * Use this when extracting time parts from ISO timestamps
 */
export function toLocalTimeString(date: Date | string): string {
    const d = typeof date === 'string' ? new Date(date) : date;
    const hours = d.getHours().toString().padStart(2, '0');
    const minutes = d.getMinutes().toString().padStart(2, '0');
    return `${hours}:${minutes}`;
}

/**
 * Combine a date string and time string into a datetime string
 * Returns format: "YYYY-MM-DD HH:mm:ss" for backend consumption
 * 
 * @param dateInput - Date as string (any format) or Date object
 * @param timeStr - Time as "HH:mm" string
 */
export function combineDateAndTime(dateInput: Date | string, timeStr: string): string {
    const datePart = toLocalDateString(dateInput);
    const [hours, minutes] = timeStr.split(':').map(Number);
    const h = hours.toString().padStart(2, '0');
    const m = minutes.toString().padStart(2, '0');
    return `${datePart} ${h}:${m}:00`;
}

/**
 * Format a datetime for display (12-hour format with AM/PM)
 */
export function formatTimeDisplay(dateString: string | null): string {
    if (!dateString) return '--:--';
    return new Date(dateString).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
    });
}

/**
 * Format a date for display (short format)
 */
export function formatDateDisplay(dateString: string): string {
    return new Date(dateString).toLocaleDateString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
    });
}

/**
 * Format a date for display (long format)
 */
export function formatDateLong(dateString: string): string {
    return new Date(dateString).toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}

/**
 * Get today's date in YYYY-MM-DD format (local timezone)
 */
export function getTodayString(): string {
    return toLocalDateString(new Date());
}

/**
 * Check if a date string represents today (in local timezone)
 */
export function isToday(dateString: string): boolean {
    return toLocalDateString(dateString) === getTodayString();
}

/**
 * Format minutes to hours string (e.g., "8h 30m")
 */
export function formatMinutesToHours(minutes: number | null): string {
    if (minutes === null || minutes === undefined) return '-';
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return `${hours}h ${mins}m`;
}
