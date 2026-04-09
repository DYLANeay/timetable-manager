// Assigns a consistent color to each user based on their ID
const COLORS = [
  { bg: 'bg-blue-100 dark:bg-blue-950/40', avatar: 'bg-blue-500', text: 'text-blue-900 dark:text-blue-200' },
  { bg: 'bg-violet-100 dark:bg-violet-950/40', avatar: 'bg-violet-500', text: 'text-violet-900 dark:text-violet-200' },
  { bg: 'bg-rose-100 dark:bg-rose-950/40', avatar: 'bg-rose-500', text: 'text-rose-900 dark:text-rose-200' },
  { bg: 'bg-amber-100 dark:bg-amber-950/40', avatar: 'bg-amber-500', text: 'text-amber-900 dark:text-amber-200' },
  { bg: 'bg-teal-100 dark:bg-teal-950/40', avatar: 'bg-teal-500', text: 'text-teal-900 dark:text-teal-200' },
  { bg: 'bg-pink-100 dark:bg-pink-950/40', avatar: 'bg-pink-500', text: 'text-pink-900 dark:text-pink-200' },
  { bg: 'bg-indigo-100 dark:bg-indigo-950/40', avatar: 'bg-indigo-500', text: 'text-indigo-900 dark:text-indigo-200' },
  { bg: 'bg-lime-100 dark:bg-lime-950/40', avatar: 'bg-lime-600', text: 'text-lime-900 dark:text-lime-200' },
]

export function getUserColor(userId: number) {
  return COLORS[userId % COLORS.length]!
}

export function getInitials(name: string): string {
  return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2)
}
