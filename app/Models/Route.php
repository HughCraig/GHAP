<?php

namespace TLCMap\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Route extends Model
{
    protected $table = 'tlcmap.route';
    public $timestamps = true;
    public $incrementing = true;

    protected $fillable = [
        'dataset_id', 'title', 'description', 'original_id', "size"
    ];

    public function dataset()
    {
        return $this->belongsTo(Dataset::class, 'dataset_id');
    }

    public function dataitems()
    {
        return $this->hasMany(Dataitem::class, 'route_id');
    }

    /**
     * Get dataitems with their position and stop index for this route.
     *
     * @param string $direction The sorting direction ('asc' or 'desc')
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function dataitemsWithPositionAndStopIdx($direction = 'asc')
    {
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';
        $sqlDirection = strtoupper($direction);

        return $this->belongsToMany(Dataitem::class, 'tlcmap.route_order')
            ->withPivot('position')
            ->select('tlcmap.dataitem.*', 'tlcmap.route_order.position')
            ->selectRaw("ROW_NUMBER() OVER (ORDER BY tlcmap.route_order.position {$sqlDirection}) as stop_idx")
            ->orderBy('tlcmap.route_order.position', $direction);
    }

    public function allStopIndices($direction = 'asc')
    {
        $orderDirection = $direction === 'desc' ? 'DESC' : 'ASC';

        return DB::table('tlcmap.route_order')
            ->where('route_id', $this->id)
            ->selectRaw('ROW_NUMBER() OVER (ORDER BY position ' . $orderDirection . ') as stop_idx, dataitem_id')
            ->orderBy('position', $direction)
            ->pluck('stop_idx', 'dataitem_id')
            ->toArray();
    }

    /**
     * Get dataitem IDs ordered by their position in the route
     *
     * This method retrieves the IDs of dataitems associated with the route,
     * ordered by their position. It utilizes the dataitemsWithPositionAndStopIdx
     * method to ensure consistent ordering.
     *
     * @param string $direction The direction of ordering ('asc' or 'desc')
     * @return array An array of dataitem IDs ordered by their position
     */
    public function getDataitemIdsOrderedByPosition($direction = 'asc')
    {
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';

        return $this->dataitemsWithPositionAndStopIdx($direction)
            ->pluck('id')
            ->toArray();
    }

    /**
     * Insert multiple records and retrieve the newly created model instances.
     *
     * This method performs a bulk insert of the given records and then retrieves
     * the newly created model instances, maintaining the original order of the input data.
     * It uses a single database query to insert and another to retrieve the records,
     * making it more efficient than individual inserts.
     *
     * @param array $records An array of associative arrays, each representing a record to be inserted.
     *                       Each record should have 'title', 'description', and 'dataset_id' keys.
     * @return \Illuminate\Database\Eloquent\Collection A collection of the newly created model instances.
     */
    public static function insertAndRetrieve(array $records)
    {
        if (empty($records)) {
            return collect();
        }

        $now = now();
        $recordsWithTimestamps = array_map(function ($record) use ($now) {
            $record['created_at'] = $now;
            $record['updated_at'] = $now;
            return $record;
        }, $records);

        self::insert($recordsWithTimestamps);

        $placeholders = implode(',', array_fill(0, count($records), '(?,?,?)'));


        $bindings = collect($records)->flatMap(function ($record) {
            return [$record['title'], $record['description'], $record['dataset_id']];
        })->all();

        $query = "
            WITH numbered AS (
                SELECT *, ROW_NUMBER() OVER (
                    PARTITION BY title, description, dataset_id
                    ORDER BY id DESC
                ) as row_num
                FROM tlcmap.route
                WHERE (title, description, dataset_id) IN ($placeholders)
            ),
            ordered_data(title, description, dataset_id, ord) AS (
                VALUES " . implode(',', array_fill(0, count($records), '(?,?,?::bigint,?)')) . "
            )
            SELECT n.*
            FROM numbered n
            JOIN ordered_data od ON n.title = od.title
                AND n.description = od.description
                AND n.dataset_id = od.dataset_id
            WHERE n.row_num = 1
            ORDER BY od.ord
        ";

        $orderBindings = collect($records)->flatMap(function ($record, $index) {
            return [$record['title'], $record['description'], $record['dataset_id'], $index];
        })->all();

        $bindings = array_merge($bindings, $orderBindings);

        return self::hydrate(DB::select($query, $bindings));
    }

    /**
     * Calculate position values for one or more new or existing dataitems
     *
     * This method calculates the position values for dataitems to be inserted or moved within the route.
     * It can handle insertions or movements at the beginning, end, or middle of the route.
     *
     * @param int|null $stopIdx The target stop_idx where dataitems should be inserted or moved to. If null, insert at the end.
     * @param int $count The number of dataitems to be inserted or moved.
     * @param array $modifiedDataitemIds An array of IDs for dataitems that are being inserted or moved. These will be excluded from index calculations.
     * @return array Returns an array of new position values for the dataitems.
     */
    public function calculateNewPositions($stopIdx = null, $count, $modifiedDataitemIds = [])
    {
        // Get all items and filter out the ones that are being moved
        $items = $this->dataitemsWithPositionAndStopIdx()->get();
        $nonMovingItems = $items->whereNotIn('id', $modifiedDataitemIds);
        $nonMovingItemCount = $nonMovingItems->count();

        // Handle case when there are no items or all items are being moved
        if ($items->isEmpty() || $nonMovingItems->isEmpty()) {
            return array_map(function ($i) {
                return 1000 * ($i + 1);
            }, range(0, $count - 1));
        }

        // Handle insertion at the end or beyond the last item
        if ($stopIdx === null || $stopIdx > $nonMovingItemCount) {
            $lastPosition = $nonMovingItems->last()->position;
            return array_map(function ($i) use ($lastPosition) {
                return $lastPosition + 1000 * ($i + 1);
            }, range(0, $count - 1));
        }

        // Determine the items before and after the insertion point
        $beforeItem = $stopIdx > 1 ? $nonMovingItems->values()->get($stopIdx - 2) : null;
        $afterItem = $stopIdx <= $nonMovingItemCount ? $nonMovingItems->values()->get($stopIdx - 1) : null;

        // Calculate the available space for insertion
        $beforePosition = $beforeItem ? $beforeItem->position : 0;
        $afterPosition = $afterItem ? $afterItem->position : ($nonMovingItems->last()->position + 1000);
        $availableSpace = $afterPosition - $beforePosition;

        // Calculate the increment between new positions
        $increment = max(1, floor($availableSpace / ($count + 1)));

        // Generate new positions
        $newPositions = [];
        for ($i = 0; $i < $count; $i++) {
            $newPositions[] = $beforePosition + ($i + 1) * $increment;
        }

        // Ensure all positions are integers
        $newPositions = array_map('intval', $newPositions);

        // Check for duplicate positions
        if (count(array_unique($newPositions)) < count($newPositions)) {
            // If duplicates exist, rebalance all positions and recalculate
            // $this->rebalancePositions();
            static::rebalancePositionsBatch([$this->id]);
            return $this->calculateNewPositions($stopIdx, $count, $modifiedDataitemIds);
        }

        return $newPositions;
    }

    /**
     * Check and update statuses for multiple routes
     *
     * This method performs the following operations:
     * 1. Retrieves route data and statistics
     * 2. Updates route sizes
     * 3. Deletes empty routes
     * 4. Identifies routes that need position rebalancing
     *
     * @param array $routeIds An array of route IDs to process
     * @return array An associative array containing deleted route IDs and warning messages
     */
    public static function checkAndUpdateStatuses(array $routeIds)
    {
        $deletedRouteIds = [];
        $warnings = [];

        $routeCounts = DB::table('tlcmap.route as r')
            ->whereIn('r.id', $routeIds)
            ->leftJoin('tlcmap.route_order as ro', 'r.id', '=', 'ro.route_id')
            ->select('r.id as route_id', 'r.size as previous_size')
            ->selectRaw('COALESCE(COUNT(ro.id), 0) as count, COALESCE(MAX(ro.position), 0) as max_position')
            ->groupBy('r.id')
            ->get()
            ->keyBy('route_id');

        // Prepare data for route size updates, deletions, and rebalancing
        $updatedRoutes = [];
        $rebalanceRouteIds = [];
        foreach ($routeIds as $routeId) {
            $routeData = $routeCounts->get($routeId);
            if (!$routeData) {
                Log::warning("Route not found: {$routeId}. Type of routeId: " . gettype($routeId));
                Log::info("Route data for ID {$routeId}: " . json_encode($routeData, JSON_PRETTY_PRINT));
                throw new \Exception("Critical error: Route {$routeId} not found.");
                continue;
            }
            $newCount = $routeData->count;
            $previousSize = $routeData->previous_size;
            $maxPosition = $routeData->max_position;

            if ($newCount > 0) {
                // Update route size
                $updatedRoutes[] = [
                    'id' => $routeId,
                    'size' => $newCount
                ];

                // Check if rebalancing is needed
                $affectedCount = abs($newCount - $previousSize);
                if (
                    $maxPosition > $newCount * 1000 &&
                    $affectedCount > 1000 &&
                    $affectedCount > $newCount * 0.1
                ) {
                    $rebalanceRouteIds[] = $routeId;
                }
            } else {
                // Mark route for deletion
                $deletedRouteIds[] = $routeId;
                $warnings[] = "Route {$routeId} has been removed as it no longer contains any places.";
            }
        }

        // Execute bulk update of route sizes
        if (!empty($updatedRoutes)) {
            $cases = [];
            foreach ($updatedRoutes as $update) {
                $cases[] = "WHEN {$update['id']} THEN {$update['size']}";
            }
            $caseStatement = implode(' ', $cases);
            $updateRouteIds = implode(',', array_column($updatedRoutes, 'id'));

            DB::update("UPDATE tlcmap.route SET size = CASE id {$caseStatement} END WHERE id IN ({$updateRouteIds})");
        }

        // Execute bulk deletion of route sizes
        if (!empty($deletedRouteIds)) {
            static::whereIn('id', $deletedRouteIds)->delete();
        }

        //  Rebalance positions for identified routes
        if (!empty($rebalanceRouteIds)) {
            static::rebalancePositionsBatch($rebalanceRouteIds);
        }

        return [
            'deletedRouteIds' => $deletedRouteIds,
            'warnings' => $warnings
        ];
    }

    /**
     * Rebalance positions for multiple routes in batches
     *
     * This method rebalances the positions of route orders for the given route IDs.
     * It processes routes in batches and updates positions in chunks to optimize performance.
     *
     * @param array $routeIds An array of route IDs to rebalance
     * @param int $routeBatchSize The number of routes to process in each batch
     * @param int $routeOrderBatchSize The number of route orders to update in each database operation
     *
     * Alternative optimization strategies in the future:
     *
     * 1. Batch Update:
     *    Use Laravel's batch update feature to reduce database queries.
     *    Suitable for medium-sized datasets.
     *    Example: Dataitem::upsert($updates, ['id'], ['position']);
     *
     * 2. Chunked Processing:
     *    Process data in chunks to avoid loading all data into memory at once.
     *    Useful for very large datasets.
     *    Example: $this->dataitemsWithPositionAndStopIdx()->orderBy('stop_idx')->chunk(1000, function ($dataitems) {...});
     *
     * 3. Sparse Rebalancing:
     *    Rebalance only a portion of the data when necessary.
     *    Efficient for partial updates in large routes.
     *    Example: Implement a method that takes start and end stop_idx parameters.
     *
     * 4. Asynchronous Processing:
     *    Use job queues for asynchronous processing of very large datasets.
     *    Example: RebalancePositionsJob::dispatch($this->id);
     */
    public static function rebalancePositionsBatch(
        array $routeIds,
        $routeBatchSize = 100,
        $routeOrderBatchSize = 1000
    ) {
        // Process routes in batches
        foreach (array_chunk($routeIds, $routeBatchSize) as $chunk) {
            $positionsToUpdate = [];

            // Fetch all route orders for the current batch of routes
            $orders = DB::table('tlcmap.route_order')
                ->whereIn('route_id', $chunk)
                ->orderBy('route_id')
                ->orderBy('position')
                ->select('id', 'route_id', 'position')
                ->get();

            // Calculate new positions
            $currentRouteId = null;
            $counter = 0;
            foreach ($orders as $order) {
                if ($currentRouteId !== $order->route_id) {
                    $currentRouteId = $order->route_id;
                    $counter = 0;
                }
                $counter++;
                $positionsToUpdate[] = [
                    'id' => $order->id,
                    'new_position' => $counter * 1000
                ];
            }

            // Update positions in the database
            DB::beginTransaction();
            try {
                // Process updates in chunks
                foreach (array_chunk($positionsToUpdate, $routeOrderBatchSize) as $updateChunk) {
                    $cases = [];
                    $ids = [];
                    foreach ($updateChunk as $update) {
                        $cases[] = "WHEN {$update['id']} THEN {$update['new_position']}";
                        $ids[] = $update['id'];
                    }
                    $ids = implode(',', $ids);
                    $cases = implode(' ', $cases);
                    DB::update("UPDATE tlcmap.route_order SET position = CASE id {$cases} END WHERE id IN ({$ids})");
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        }
    }


    /**
     * Add one or more dataitems to a specified route
     *
     * @param Route $route The route to add dataitems to
     * @param array $dataitemIds Array of dataitem IDs to be added
     * @param string|null|int $stopIdx Specified insertion position, can be a number or "append"
     * @return array Associative array with 'status' (bool) and 'message' (string)
     */
    public static function addDataitemsToRoute(
        array $dataitemIds,
        $stopIdx = null,
        $routeId = null,
        string $newRouteTitle = null,
        string $newRouteDescription = null,
        $datasetId = null
    ) {
        // Set stopIdx to null if it's "append" or null to add at the end of the route
        $stopIdx = (strtolower($stopIdx) === 'append' || $stopIdx === null) ? null : $stopIdx;

        // Validate and process stopIdx
        if ($stopIdx !== null) {
            $stopIdx = intval($stopIdx);
            if ($stopIdx < 1) {
                throw new \InvalidArgumentException('Invalid stop_idx provided.');
            }
        }
        $addedCount = count($dataitemIds);

        $processWarning = '';

        // Start database transaction
        DB::beginTransaction();

        try {
            $route = self::findOrCreateRoute(
                $routeId,
                $newRouteTitle,
                $newRouteDescription,
                $datasetId
            );

            // Calculate new positions
            $newPositions = $route->calculateNewPositions($stopIdx, $addedCount);

            $inserts = [];
            foreach ($dataitemIds as $index => $dataitemId) {
                // Update route order table
                $inserts[] = [
                    'route_id' => $route->id,
                    'dataitem_id' => $dataitemId,
                    'position' => $newPositions[$index],
                ];
            }
            $result = self::upsertPositionAndRouteIdBatch($inserts);

            // Batch update route_id for dataitems
            Dataitem::updateRouteIdBatch($route->id, $dataitemIds);
            if (!$result) {
                LOG::info(json_encode($inserts));
                throw new \Exception("Failed to update positions and route IDs");
            }

            // Check and update route status
            // $routeStatus = $route->checkAndUpdateStatus($addedCount);
            // $processWarning = $processWarning . $routeStatus['message'];
            $routeStatus = Route::checkAndUpdateStatuses([$route->id]);
            $processWarning = $processWarning . ($routeStatus['warnings'][0] ?? '');;

            DB::commit();
            return ['status' => true, 'message' => $processWarning];
        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($route)) {
                $processWarning = "Fail to add new place(s): " . implode(', ', $dataitemIds) . " to Route " . $route->id . ". ";
            } else {
                $processWarning = "Fail to add new place(s): " . implode(', ', $dataitemIds) . " to new route " . ". ";
            }

            LOG::error($processWarning . "Error: " . $e->getMessage());
            return ['status' => false, 'message' => $processWarning];
        }
    }

    /**
     * Remove one or more dataitems from a route
     *
     * @param array $dataitemIds Array of dataitem IDs to be removed
     * @return array Associative array with 'status' (bool) and 'message' (string)
     */
    public function removeDataitemsFromRoute(array $dataitemIds)
    {

        $processWarning = '';

        DB::beginTransaction();

        try {
            // Remove records from route_order table
            $removedCount = DB::table('tlcmap.route_order')
                ->where('route_id', $this->id)
                ->whereIn('dataitem_id', $dataitemIds)
                ->delete();

            // Clear route_id for removed dataitems
            Dataitem::clearRouteIdBatch($dataitemIds);

            // Check and update route status
            // $routeStatus = $this->checkAndUpdateStatus($removedCount);
            // $processWarning = $processWarning . $routeStatus['message'];
            $routeStatus = Route::checkAndUpdateStatuses([$this->id]);
            $processWarning = $processWarning . ($routeStatus['warnings'][0] ?? '');;


            DB::commit();
            return ['status' => true, 'message' => $processWarning];
        } catch (\Exception $e) {
            DB::rollBack();
            $processWarning = "Fail to remove places: " . implode(', ', $dataitemIds) . " from Route " . $this->id . ". ";
            LOG::error($processWarning . "Error: " . $e->getMessage());
            return ['status' => false, 'message' => $processWarning];
        }
    }

    /**
     * Delete multiple route order entries based on dataitem IDs.
     *
     * @param array $dataitemIds An array of dataitem IDs to match against
     * @return int The number of deleted records
     */
    public static function deleteRouteOrdersByDataitemIds(array $dataitemIds)
    {
        $placeholders = implode(',', array_fill(0, count($dataitemIds), '?'));

        $result = DB::select("
            WITH deleted_orders AS (
                DELETE FROM tlcmap.route_order
                WHERE dataitem_id IN ({$placeholders})
                RETURNING route_id
            )
            SELECT ARRAY_AGG(DISTINCT route_id) as affected_route_ids
            FROM deleted_orders
        ", $dataitemIds);

        $affectedRouteIds = $result[0]->affected_route_ids;

        if (is_string($affectedRouteIds)) {
            $affectedRouteIds = json_decode($affectedRouteIds, true);
        } elseif (!is_array($affectedRouteIds)) {
            $affectedRouteIds = [$affectedRouteIds];
        }

        return $affectedRouteIds;
    }

    /**
     * Update positions of dataitems within a route
     *
     * @param Route $route The route to update
     * @param array $dataitemIds Array of dataitem IDs to be moved
     * @param int $stopIdx The target stop index where dataitems should be moved to
     * @return bool Whether the operation was successful
     */
    public function updateDataitemsPositionInRoute(array $dataitemIds, $stopIdx)
    {
        $updatedCount = count($dataitemIds);
        $processWarning = '';
        // Calculate new positions
        $newPositions = $this->calculateNewPositions($stopIdx, $updatedCount, $dataitemIds);

        DB::beginTransaction();

        try {
            // Prepare updates for batch processing
            $updates = [];
            foreach ($dataitemIds as $index => $dataitemId) {
                $updates[] = [
                    'dataitem_id' => $dataitemId,
                    'position' => $newPositions[$index]
                ];
            }

            // Execute batch update
            $this->updatePositionBatch($updates);

            DB::commit();
            return ['status' => true, 'message' => $processWarning];
        } catch (\Exception $e) {
            DB::rollBack();
            $processWarning = "Fail to update places: " . implode(', ', $dataitemIds) . " in Route " . $this->id . ". ";
            LOG::error($processWarning . "Error: " . $e->getMessage());
            return ['status' => false, 'message' => $processWarning];
        }
    }

    /**
     * Move one or more dataitems between exisint route to EXISITNG or NEW route
     *
     * @param int $fromRouteId The ID of the source route
     * @param int $toRouteId The ID of the destination route
     * @param string $toRouteTitle The title of the destination route
     * @param string $toRouteDescription The description of the destination route
     * @param int $toDatasetId The dataset ID of the destination route
     * @param array $dataitemIds An array of dataitem IDs to be moved
     * @param int|string|null $stopIdx The stop index for insertion, 'append' or null to add at the end
     * @return array Status and result of the operation
     */
    public static function moveDataitemsBetweenRoutes(
        $fromRouteId,
        $toRouteId,
        $toRouteTitle,
        $toRouteDescription,
        $toDatasetId,
        array $dataitemIds,
        $stopIdx = null
    ) {
        $processWarning = '';
        DB::beginTransaction();

        try {
            // Get the current route
            $fromRoute = self::findOrCreateRoute($fromRouteId);
            $toRoute = self::findOrCreateRoute($toRouteId, $toRouteTitle, $toRouteDescription, $toDatasetId);

            // Set stopIdx to null if it's "append" or null to add at the end of the route
            $stopIdx = (strtolower($stopIdx) === 'append' || $stopIdx === null) ? null : $stopIdx;

            // Validate and process stopIdx
            if ($stopIdx !== null) {
                $stopIdx = intval($stopIdx);
                if ($stopIdx < 1) {
                    throw new \InvalidArgumentException('Invalid stop_idx provided.');
                }
            }

            // Calculate new positions
            $newPositions = $toRoute->calculateNewPositions($stopIdx, count($dataitemIds));

            // Prepare batch update data
            $updates = [];
            foreach ($dataitemIds as $index => $dataitemId) {
                $updates[] = [
                    'dataitem_id' => $dataitemId,
                    'route_id' => $toRoute->id,
                    'position' => $newPositions[$index]
                ];
            }

            // Update route_id & position in route_order table
            $result = self::upsertPositionAndRouteIdBatch($updates);
            if (!$result) {
                throw new \Exception("Failed to update positions and route IDs");
            }

            // Update route_id in dataitem table
            Dataitem::updateRouteIdBatch($toRoute->id, $dataitemIds);

            if (!$result) {
                throw new \Exception("Failed to update positions and route IDs");
            }

            // Update status of from and to route
            // $fromRouteStatus = $fromRoute->checkAndUpdateStatus(count($dataitemIds));
            // $processWarning = $processWarning . $fromRouteStatus['message'];
            $fromRouteStatus = Route::checkAndUpdateStatuses([$fromRoute->id]);
            $processWarning = $processWarning . ($fromRouteStatus['warnings'][0] ?? '');;
            // $toRouteStatus = $toRoute->checkAndUpdateStatus(count($dataitemIds));
            // $processWarning = $processWarning . $toRouteStatus['message'];
            $toRouteStatus = Route::checkAndUpdateStatuses([$toRoute->id]);
            $processWarning = $processWarning . ($toRouteStatus['warnings'][0] ?? '');;

            // Update metadata of to route
            if (!is_null($toRouteTitle) || !is_null($toRouteDescription)) {
                $toRoute->fill([
                    "title" => $toRouteTitle,
                    "description" => $toRouteDescription,
                ]);
                $toRoute->save();
            }

            DB::commit();
            return [
                'status' => true,
                'message' => $processWarning,
                'newRoute' => $toRoute
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            $errorMessage = "Failed to move dataitems between routes. Error: " . $e->getMessage();
            LOG::error($errorMessage);
            return ['status' => false, 'message' => $errorMessage];
        }
    }

    /**
     * Update positions for multiple dataitems (tlcmap.route_order) within same route in batch
     *
     * This method performs a bulk update of positions for multiple dataitems within the route.
     * It uses a single SQL query to efficiently update all specified dataitems' positions.
     *
     * @param array $updates An array of associative arrays, each containing 'dataitem_id' and 'position' for updating.
     * @return int The number of affected rows in the database.
     */
    public function updatePositionBatch(array $updates)
    {
        $values = [];
        $params = [];
        foreach ($updates as $update) {
            $values[] = "(CAST(? AS BIGINT), CAST(? AS BIGINT))";
            $params[] = $update['dataitem_id'];
            $params[] = $update['position'];
        }

        $valuesString = implode(', ', $values);

        $sql = "
            UPDATE tlcmap.route_order
            SET position = updates.new_position
            FROM (VALUES {$valuesString}) AS updates(dataitem_id, new_position)
            WHERE route_order.dataitem_id = updates.dataitem_id
              AND route_order.route_id = ?
        ";

        $params[] = $this->id; // add route_id to parameter array

        return DB::affectingStatement($sql, $params);
    }

    /**
     * Upsert positions and route IDs for multiple dataitems in batch
     *
     * This method performs a bulk upsert operation on the tlcmap.route_order table
     * using PostgreSQL's native UPSERT functionality.
     *
     * @param array $dataitemRoutePositions An array of associative arrays, each containing:
     *                           - 'dataitem_id': The ID of the dataitem
     *                           - 'route_id': The ID of the route
     *                           - 'position': The position of the dataitem in the route
     * @return bool True if the upsert was successful, false otherwise
     */
    public static function upsertPositionAndRouteIdBatch(array $dataitemRoutePositions)
    {
        if (empty($dataitemRoutePositions)) {
            return true; // If no data to update, return success
        }

        $values = [];
        $params = [];

        foreach ($dataitemRoutePositions as $order) {
            $values[] = "(CAST(? AS BIGINT), CAST(? AS BIGINT), CAST(? AS BIGINT))";
            $params[] = $order['dataitem_id'];
            $params[] = $order['route_id'];
            $params[] = $order['position'];
        }

        $valuesString = implode(', ', $values);

        DB::beginTransaction();

        try {
            $sql = "
            INSERT INTO tlcmap.route_order (dataitem_id, route_id, position)
            VALUES {$valuesString}
            ON CONFLICT (dataitem_id)
            DO UPDATE SET
                route_id = EXCLUDED.route_id,
                position = EXCLUDED.position
            ";
            DB::affectingStatement($sql, $params);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to upsert positions and route IDs in batch. Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get the details of the current Route instance.
     *
     * @return array An associative array containing the details of the Route, including:
     *               - 'title' (string): The title of the Route.
     *               - 'description' (string|null): The description of the Route.
     *               - 'allStopIndices' (array): An array of stop indices for the Route.
     */
    public function currentDetails($direction = 'asc')
    {
        $details = [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'allStopIndices' => $this->allStopIndices($direction),
        ];

        return $details;
    }

    /**
     * Get the count of data items associated with the route.
     *
     * @return int The count of dataitems.
     */
    public function getDataItemCount()
    {
        return $this->dataitems()->count();
    }



    /**
     * Find or create a Route instance.
     *
     * @param int|null $routeId The ID of the Route instance to find.
     * @param string|null $routeTitle The title of the new Route instance to create.
     * @param string|null $routeDescription The description of the new Route instance to create.
     * @return Route The found or created Route instance.
     * @throws InvalidArgumentException If the provided arguments are invalid.
     */
    public static function findOrCreateRoute(
        $routeId = null,
        $routeTitle = null,
        $routeDescription = null,
        $dataset_id = null
    ) {
        if (!is_null($routeId) && Route::whereKey($routeId)->exists()) {
            return Route::findOrFail($routeId);
        }

        if (!is_null($routeTitle) && $routeTitle !== '') {
            return Route::create([
                'title' => $routeTitle,
                'description' => $routeDescription,
                'dataset_id' => $dataset_id
            ]);
        }

        throw new \InvalidArgumentException('Invalid arguments provided for finding or creating a Route.');
    }

    /**
     * Get the ordered Dataitem data based on the specified display mode (start time (datestart) or end time (dateend))
     * If the value of orderColumn is null, null will put to last.
     *
     * @todo Might get error when user input various format of date fields
     *
     * @param string $displayMode The display mode, can be 'timestart' or 'timeend'
     * @return \Illuminate\Database\Eloquent\Collection The ordered Dataitem data
     */
    public function getDateOrderedDataitems($displayMode = 'timestart', $direction = 'asc')
    {
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';
        $sqlDirection = strtoupper($direction);
        $orderColumn = $displayMode === 'timeend' ? 'dateend' : 'datestart';

        return $this->dataitems()
            ->select('*', DB::raw("ROW_NUMBER() OVER (ORDER BY {$orderColumn} {$sqlDirection} NULLS LAST) as time_stop_idx"))
            ->get();
    }

    /**
     * Get the ordered coordinate data
     * If the ordered Dataitem data is not provided, it will automatically call the getDateOrderedDataitems method to get the ordered data
     *
     * @param string $displayMode The display mode, can be 'timestart', 'timeend' or 'route'
     * @return \Illuminate\Support\Collection The ordered coordinate data, including latitude and longitude
     */
    public function getOrderedCoordinates($displayMode = 'route')
    {
        $routeCoords = [];

        if ($displayMode === 'timestart') {
            $orderedDataitems = $this->getDateOrderedDataitems();
        } else if ($displayMode === 'timeend') {
            $orderedDataitems = $this->getDateOrderedDataitems('timeend');
        } else {
            $orderedDataitems = $this->dataitemsWithPositionAndStopIdx()->get();
        }
        foreach ($orderedDataitems as $dataitem) {
            if ($dataitem->longitude !== null && $dataitem->latitude !== null) {
                $routeCoords[] = [$dataitem->longitude, $dataitem->latitude];
            }
        }
        return $routeCoords;
    }

    /**
     * Batch update route metadata (title, description, size) for multiple routes.
     *
     * @param \Illuminate\Support\Collection|array $routes A collection or array of route data, each containing 'id' and optionally 'title', 'description', and 'size'
     * @return int The number of affected rows
     */
    public static function updateRouteMetadataBatch($routes)
    {
        $fields = ['title', 'description', 'size'];
        $cases = array_fill_keys($fields, []);
        $ids = [];
        $params = [];

        // Prepare the CASE statements for each field
        foreach ($routes as $route) {
            $ids[] = $route['id'];

            foreach ($fields as $field) {
                if (array_key_exists($field, $route)) {
                    $cases[$field][] = "WHEN {$route['id']} THEN ?";
                    $params[] = $route[$field];
                }
            }
        }

        $updateClauses = [];
        foreach ($fields as $field) {
            if (!empty($cases[$field])) {
                $updateClauses[] = "{$field} = CASE id " . implode(' ', $cases[$field]) . " ELSE {$field} END";
            }
        }

        $sql = "UPDATE tlcmap.route SET " . implode(', ', $updateClauses) . " WHERE id IN (" . implode(',', $ids) . ")";

        // Execute the update query
        return DB::update($sql, $params);
    }

    /**
     * @todo Remove it if checkAndUpdateStatuses test finish
     * Rebalance position values of all dataitems in the route
     *
     * This method uses a native SQL query to efficiently redistribute
     * the position values of all dataitems in the route.
     *
     * Alternative optimization strategies in the future:
     *
     * 1. Batch Update:
     *    Use Laravel's batch update feature to reduce database queries.
     *    Suitable for medium-sized datasets.
     *    Example: Dataitem::upsert($updates, ['id'], ['position']);
     *
     * 2. Chunked Processing:
     *    Process data in chunks to avoid loading all data into memory at once.
     *    Useful for very large datasets.
     *    Example: $this->dataitemsWithPositionAndStopIdx()->orderBy('stop_idx')->chunk(1000, function ($dataitems) {...});
     *
     * 3. Sparse Rebalancing:
     *    Rebalance only a portion of the data when necessary.
     *    Efficient for partial updates in large routes.
     *    Example: Implement a method that takes start and end stop_idx parameters.
     *
     * 4. Asynchronous Processing:
     *    Use job queues for asynchronous processing of very large datasets.
     *    Example: RebalancePositionsJob::dispatch($this->id);
     */
    public function rebalancePositions()
    {
        DB::statement("
            UPDATE tlcmap.route_order
            SET position = subquery.new_position
            FROM (
                SELECT id,
                       ROW_NUMBER() OVER (ORDER BY position) * 1000 AS new_position
                FROM tlcmap.route_order
                WHERE route_id = ?
            ) AS subquery
            WHERE tlcmap.route_order.id = subquery.id
              AND tlcmap.route_order.route_id = ?
        ", [$this->id, $this->id]);
    }

    /**
     * @todo Remove it if checkAndUpdateStatuses test finish
     * Check and update the route status, including size and position rebalancing
     *
     * @param int $affectedCount Number of dataitems added or removed
     * @return string Warning message if route is deleted, empty string otherwise
     */
    public function checkAndUpdateStatus($affectedCount = 1)
    {
        $newSize = $this->getDataItemCount();
        $processWarning = '';
        $routeDeleted = false;

        if ($newSize > 0) {
            $this->size = $newSize;
            $this->save();

            // Check if rebalancing is necessary
            if ($affectedCount > 1000 && $affectedCount > $this->size * 0.1) { // Threshold, e.g., 10%
                $lastPosition = DB::table('tlcmap.route_order')
                    ->where('route_id', $this->id)
                    ->max('position');

                if ($lastPosition > $this->size * 1000) {
                    // $this->rebalancePositions();
                    static::rebalancePositionsBatch([$this->id]);
                }
            }
        } else {
            $this->delete();
            $routeDeleted = true;
            $processWarning = "Your action has removed the last place from <b>Route "
                . $this->id . "</b>. Route "
                . $this->id . " has been removed.<br>" . $processWarning;
        }

        return ['deleted' => $routeDeleted, 'message' => $processWarning];
    }
}
