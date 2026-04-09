<?php

namespace App\Enums;

enum SwapRequestStatus: string
{
    case PendingPeer = 'pending_peer';
    case PeerAccepted = 'peer_accepted';
    case PeerDeclined = 'peer_declined';
    case ManagerApproved = 'manager_approved';
    case ManagerDenied = 'manager_denied';
    case Cancelled = 'cancelled';
}
