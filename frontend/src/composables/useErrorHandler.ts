import { addToast } from './useToast'
import { getOfflineStatus } from './useOffline'

export interface ErrorContext {
  action: string
  silent?: boolean
}

export class AppError extends Error {
  constructor(
    message: string,
    public code: string,
    public retry?: () => Promise<void>
  ) {
    super(message)
    this.name = 'AppError'
  }
}

export function handleError(error: unknown, context: ErrorContext): AppError {
  const { isOffline } = getOfflineStatus()

  if (isOffline) {
    const appError = new AppError(
      'Vous êtes hors ligne. Veuillez vérifier votre connexion.',
      'OFFLINE'
    )
    if (!context.silent) {
      addToast(appError.message, 'error')
    }
    return appError
  }

  if (error instanceof AppError) {
    if (!context.silent) {
      addToast(error.message, 'error')
    }
    return error
  }

  if (error instanceof TypeError && error.message === 'Failed to fetch') {
    const appError = new AppError(
      'Impossible de se connecter au serveur. Vérifiez votre connexion ou réessayez.',
      'NETWORK_ERROR'
    )
    if (!context.silent) {
      addToast(appError.message, 'error')
    }
    return appError
  }

  // Default error
  const appError = new AppError(
    `Une erreur est survenue${context.action ? ` lors de ${context.action}` : ''}. Veuillez réessayer.`,
    'UNKNOWN'
  )
  if (!context.silent) {
    addToast(appError.message, 'error')
  }
  return appError
}

export async function withErrorHandling<T>(
  fn: () => Promise<T>,
  context: ErrorContext
): Promise<T | null> {
  try {
    return await fn()
  } catch (error) {
    handleError(error, context)
    return null
  }
}
