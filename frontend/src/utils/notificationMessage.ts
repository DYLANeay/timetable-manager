import type { AppNotification } from '@/types'

type TFn = (key: string, params?: Record<string, string>) => string

export function formatNotifDate(dateStr: string, locale: string): string {
  if (!dateStr) return ''
  const loc = locale === 'fr' ? 'fr-CH' : 'en-US'
  return new Intl.DateTimeFormat(loc, { weekday: 'short', day: 'numeric', month: 'short' }).format(
    new Date(dateStr + 'T12:00:00'),
  )
}

export function getNotificationMessage(n: AppNotification, t: TFn, locale: string): string {
  const d = n.data
  const name = d.requester_name ?? ''

  switch (n.type) {
    case 'swap_request':
      return d.swap_type === 'giveaway'
        ? t('notifications.types.giveaway_request', { name })
        : t('notifications.types.swap_request', { name })
    case 'leave_request':
      return t('notifications.types.leave_request', { name })
    case 'holiday_request':
      return t('notifications.types.holiday_request', { name })
    case 'swap_targeting_you':
      return t('notifications.types.swap_targeting_you', { name })
    case 'planning_updated':
      return d.action === 'created'
        ? t('notifications.types.planning_created', {
            shift: t(`schedule.${d.shift_type ?? ''}`),
            date: formatNotifDate(d.date ?? '', locale),
          })
        : t('notifications.types.planning_deleted', {
            shift: t(`schedule.${d.shift_type ?? ''}`),
            date: formatNotifDate(d.date ?? '', locale),
          })
    case 'swap_decided':
      if (d.swap_type === 'giveaway') {
        return d.decision === 'approved'
          ? t('notifications.types.giveaway_approved', { date: formatNotifDate(d.shift_date ?? '', locale) })
          : t('notifications.types.giveaway_denied', { date: formatNotifDate(d.shift_date ?? '', locale) })
      }
      return d.decision === 'approved'
        ? t('notifications.types.swap_approved', { date: formatNotifDate(d.shift_date ?? '', locale) })
        : t('notifications.types.swap_denied', { date: formatNotifDate(d.shift_date ?? '', locale) })
    case 'leave_decided':
      return d.decision === 'approved'
        ? t('notifications.types.leave_approved', {
            start: formatNotifDate(d.start_date ?? '', locale),
            end: formatNotifDate(d.end_date ?? '', locale),
          })
        : t('notifications.types.leave_denied')
    default:
      return n.type
  }
}

export function getNotificationRoute(type: string): string {
  switch (type) {
    case 'swap_request':
    case 'swap_targeting_you':
    case 'swap_decided':
      return '/swap-requests'
    case 'leave_request':
    case 'holiday_request':
    case 'leave_decided':
      return '/leaves'
    case 'planning_updated':
    default:
      return '/schedule'
  }
}
