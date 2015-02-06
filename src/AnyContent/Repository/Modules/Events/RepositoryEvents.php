<?php

namespace AnyContent\Repository\Modules\Events;

final class RepositoryEvents
{

    const CONTENT_RECORD_BEFORE_INSERT = 'content.record.before.insert';
    const CONTENT_RECORD_AFTER_INSERt  = 'content.record.after.insert';

    const CONTENT_RECORD_BEFORE_UPDATE = 'content.record.before.update';
    const CONTENT_RECORD_AFTER_UPDATE  = 'content.record.after.update';

    const CONTENT_RECORD_BEFORE_DELETE = 'content.record.before.delete';
    const CONTENT_RECORD_AFTER_DELETE  = 'content.record.after.delete';
}